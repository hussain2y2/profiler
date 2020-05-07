<?php

namespace Isotopes\Profiler;

use Closure;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Isotopes\Profiler\Contracts\EntriesRepository;
use Isotopes\Profiler\Contracts\TerminableRepository;
use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Entry\EntryUpdate;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Traits\AuthorizesRequests;
use Isotopes\Profiler\Traits\ExtractsMailableTags;
use Isotopes\Profiler\Traits\ListensForStorageOpportunities;
use Isotopes\Profiler\Traits\RegistersWatchers;
use Illuminate\Foundation\Application;
use RuntimeException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

class Profiler
{
    use AuthorizesRequests, RegistersWatchers, ExtractsMailableTags, ListensForStorageOpportunities;

    /**
     * The callbacks that filter the entries that should be recorded.
     *
     * @var array
     */
    public static $filterUsing = [];

    /**
     * The callbacks that filter the batches that should be recorded.
     *
     * @var array
     */
    public static $filterBatchUsing = [];

    /**
     * The callback executed after queuing a new entry.
     *
     * @var Closure
     */
    public static $afterRecordingHook;

    /**
     * The callback that adds tags to the record.
     *
     * @var Closure
     */
    public static $tagUsing;

    /**
     * The list of queued entries to be stored.
     *
     * @var array
     */
    public static $entriesQueue = [];

    /**
     * The list of queued entry updates.
     *
     * @var array
     */
    public static $updatesQueue = [];

    /**
     * The list of hidden request headers.
     *
     * @var array
     */
    public static $hiddenRequestHeaders = [
        'authorization',
        'php-auth-pw',
    ];

    /**
     * The list of hidden request parameters.
     *
     * @var array
     */
    public static $hiddenRequestParameters = [
        'password',
        'password_confirmation',
    ];

    /**
     * The list of hidden response parameters.
     *
     * @var array
     */
    public static $hiddenResponseParameters = [];

    /**
     * Indicates if Profiler should ignore events fired by Laravel.
     *
     * @var bool
     */
    public static $ignoreFrameworkEvents = true;

    /**
     * Indicates if Profiler should use the dark theme.
     *
     * @var bool
     */
    public static $useDarkTheme = false;

    /**
     * Indicates if Profiler should record entries.
     *
     * @var bool
     */
    public static $shouldRecord = false;

    /**
     * Indicates if Profiler migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Register the Telescope watchers and start recording if necessary.
     *
     * @param Application $app
     * @return void
     */
    public static function start(Application $app)
    {
        if (! config('profiler.enabled')) {
            return;
        }

        static::registerWatchers($app);
        static::registerMailableTagExtractor();

        if (static::runningApprovedArtisanCommand($app) || static::handlingApprovedRequest($app)) {
            try {
                static::startRecording();
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Determine if the application is running an approved command.
     *
     * @param Application $app
     * @return bool
     */
    protected static function runningApprovedArtisanCommand(Application $app)
    {
        return $app->runningInConsole() && !in_array($_SERVER['argv'][1] ?? null, array_merge([
                // 'migrate',
                'migrate:rollback',
                'migrate:fresh',
                // 'migrate:refresh',
                'migrate:reset',
                'migrate:install',
                'package:discover',
                'queue:listen',
                'queue:work',
                'horizon',
                'horizon:work',
                'horizon:supervisor',
            ], config('profiler.ignoreCommands', []), config('profiler.ignore_commands', [])), true);
    }

    /**
     * Determine if the application is handling an approved request.
     *
     * @param Application $app
     * @return bool
     */
    protected static function handlingApprovedRequest(Application $app)
    {
        return ! $app->runningInConsole() && ! $app['request']->is(
                array_merge([
                    config('profiler.path').'*',
                    'profiler-api*',
                    'vendor/profiler*',
                    'horizon*',
                    'vendor/horizon*',
                    'nova-api*',
                ], config('profiler.ignore_paths', []))
            );
    }

    /**
     * Start recording entries.
     *
     * @return void
     * @throws Exception
     */
    public static function startRecording()
    {
        app(EntriesRepository::class)->loadMonitoredTags();

        static::$shouldRecord = ! cache('profiler:pause-recording');
    }

    /**
     * Stop recording entries.
     *
     * @return void
     */
    public static function stopRecording()
    {
        static::$shouldRecord = false;
    }

    /**
     * Execute the given callback without recording Profiler entries.
     *
     * @param callable $callback
     * @return void
     */
    public static function withoutRecording($callback)
    {
        $shouldRecord = static::$shouldRecord;

        static::$shouldRecord = false;

        call_user_func($callback);

        static::$shouldRecord = $shouldRecord;
    }

    /**
     * Determine if Profiler is recording.
     *
     * @return bool
     */
    public static function isRecording()
    {
        return static::$shouldRecord;
    }

    /**
     * Record the given entry.
     *
     * @param string $type
     * @param IncomingEntry $entry
     * @return void
     */
    protected static function record(string $type, IncomingEntry $entry)
    {
        if (!static::isRecording()) {
            return;
        }

        $entry->type($type)->tags(static::$tagUsing ? call_user_func(static::$tagUsing, $entry) : []);

        try {
            if (Auth::hasResolvedGuards() && Auth::hasUser()) {
                $entry->user(Auth::user());
            }
        } catch (Throwable $throwable) {
        }

        static::withoutRecording(static function () use ($entry) {
            if (collect(static::$filterUsing)->every->__invoke($entry)) {
                static::$entriesQueue[] = $entry;
            }

            if (static::$afterRecordingHook) {
                call_user_func(static::$afterRecordingHook, new static);
            }
        });
    }

    /**
     * Record the given entry update.
     *
     * @param EntryUpdate $update
     * @return void
     */
    public static function recordUpdate(EntryUpdate $update)
    {
        if (static::$shouldRecord) {
            static::$updatesQueue[] = $update;
        }
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordCache(IncomingEntry $entry)
    {
        static::record(EntryType::CACHE, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordCommand(IncomingEntry $entry)
    {
        static::record(EntryType::COMMAND, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordDump(IncomingEntry $entry)
    {
        static::record(EntryType::DUMP, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordEvent(IncomingEntry $entry)
    {
        static::record(EntryType::EVENT, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordException(IncomingEntry $entry)
    {
        static::record(EntryType::EXCEPTION, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordGate(IncomingEntry $entry)
    {
        static::record(EntryType::GATE, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordJob($entry)
    {
        static::record(EntryType::JOB, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordLog(IncomingEntry $entry)
    {
        static::record(EntryType::LOG, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordMail(IncomingEntry $entry)
    {
        static::record(EntryType::MAIL, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordNotification($entry)
    {
        static::record(EntryType::NOTIFICATION, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordQuery(IncomingEntry $entry)
    {
        static::record(EntryType::QUERY, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordModelEvent(IncomingEntry $entry)
    {
        static::record(EntryType::MODEL, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordRedis(IncomingEntry $entry)
    {
        static::record(EntryType::REDIS, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordRequest(IncomingEntry $entry)
    {
        static::record(EntryType::REQUEST, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordScheduledCommand(IncomingEntry $entry)
    {
        static::record(EntryType::SCHEDULED_TASK, $entry);
    }

    /**
     * Record the given entry.
     *
     * @param IncomingEntry $entry
     * @return void
     */
    public static function recordView(IncomingEntry $entry)
    {
        static::record(EntryType::VIEW, $entry);
    }

    /**
     * Flush all entries in the queue.
     *
     * @return static
     */
    public static function flushEntries()
    {
        static::$entriesQueue = [];

        return new static;
    }

    /**
     * Record the given exception.
     *
     * @param Throwable|Exception $e
     * @param array $tags
     * @return void
     */
    public static function catch($e, $tags = [])
    {
        if ($e instanceof Throwable && ! $e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }

        event(new MessageLogged('error', $e->getMessage(), ['exception' => $e, 'profiler' => $tags]));
    }

    /**
     * Set the callback that filters the entries that should be recorded.
     *
     * @param Closure $callback
     * @return static
     */
    public static function filter(Closure $callback)
    {
        static::$filterUsing[] = $callback;

        return new static;
    }

    /**
     * Set the callback that filters the batches that should be recorded.
     *
     * @param Closure $callback
     * @return static
     */
    public static function filterBatch(Closure $callback)
    {
        static::$filterBatchUsing[] = $callback;

        return new static;
    }

    /**
     * Set the callback that will be executed after an entry is recorded in the queue.
     *
     * @param Closure $callback
     * @return static
     */
    public static function afterRecording(Closure $callback)
    {
        static::$afterRecordingHook = $callback;

        return new static;
    }

    /**
     * Set the callback that adds tags to the record.
     *
     * @param Closure $callback
     * @return static
     */
    public static function tag(Closure $callback)
    {
        static::$tagUsing = $callback;

        return new static;
    }

    /**
     * Store the queued entries and flush the queue.
     *
     * @param EntriesRepository $storage
     * @return void
     */
    public static function store(EntriesRepository $storage)
    {
        if (empty(static::$entriesQueue) && empty(static::$updatesQueue)) {
            return;
        }

        if (! collect(static::$filterBatchUsing)->every->__invoke(collect(static::$entriesQueue))) {
            static::flushEntries();
        }

        try {
            $batchId = Str::orderedUuid()->toString();
            $insert_records = static::collectEntries($batchId);
            $update_records = static::collectUpdates($batchId);

            if ($insert_records->count()) {
                $storage->store($insert_records);
            }

            if ($update_records->count()) {
                $storage->update($update_records);
            }

            if ($storage instanceof TerminableRepository) {
                $storage->terminate();
            }
        } catch (Exception $e) {
            app(ExceptionHandler::class)->report($e);
        }

        static::$entriesQueue = [];
        static::$updatesQueue = [];
    }

    /**
     * Collect the entries for storage.
     *
     * @param string $batchId
     * @return Collection
     */
    protected static function collectEntries(string $batchId)
    {
        return collect(static::$entriesQueue)
            ->each(static function ($entry) use ($batchId) {
                $entry->batchId($batchId);

                if ($entry->isDump()) {
                    $entry->assignEntryPointFromBatch(static::$entriesQueue);
                }
            });
    }

    /**
     * Collect the updated entries for storage.
     *
     * @param string $batchId
     * @return Collection
     */
    protected static function collectUpdates(string $batchId)
    {
        return collect(static::$updatesQueue)
            ->each(static function ($entry) use ($batchId) {
                $entry->change(['updated_batch_id' => $batchId]);
            });
    }

    /**
     * Hide the given request header.
     *
     * @param array $headers
     * @return static
     */
    public static function hideRequestHeaders(array $headers)
    {
        static::$hiddenRequestHeaders = array_merge(
            static::$hiddenRequestHeaders,
            $headers
        );

        return new static;
    }

    /**
     * Hide the given request parameters.
     *
     * @param array $attributes
     * @return static
     */
    public static function hideRequestParameters(array $attributes)
    {
        static::$hiddenRequestParameters = array_merge(
            static::$hiddenRequestParameters,
            $attributes
        );

        return new static;
    }

    /**
     * Hide the given response parameters.
     *
     * @param array $attributes
     * @return static
     */
    public static function hideResponseParameters(array $attributes)
    {
        static::$hiddenResponseParameters = array_merge(
            static::$hiddenResponseParameters,
            $attributes
        );

        return new static;
    }

    /**
     * Specifies that Profiler should record events fired by Laravel.
     *
     * @return static
     */
    public static function recordFrameworkEvents()
    {
        static::$ignoreFrameworkEvents = false;

        return new static;
    }

    /**
     * Specifies that Profiler should use the dark theme.
     *
     * @return static
     */
    public static function night()
    {
        static::$useDarkTheme = true;

        return new static;
    }

    /**
     * Get the default JavaScript variables for Profiler.
     *
     * @return array
     * @throws Exception
     */
    public static function scriptVariables()
    {
        return [
            'path' => config('profiler.path'),
            'timezone' => config('app.timezone'),
            'recording' => ! cache('profiler:pause-recording'),
        ];
    }

    /**
     * Configure Profiler to not register its migrations.
     *
     * @return Profiler
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }

    /**
     * Check if assets are up-to-date.
     *
     * @return bool
     * @throws RuntimeException
     */
    public static function assetsAreCurrent()
    {
        $publishedPath = public_path('vendor/profiler/mix-manifest.json');

        if (!File::exists($publishedPath)) {
            throw new RuntimeException('The Profiler assets are not published. Please run: php artisan profiler:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__.'/../public/mix-manifest.json');
    }
}
