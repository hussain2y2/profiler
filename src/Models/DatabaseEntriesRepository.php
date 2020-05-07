<?php

namespace Isotopes\Profiler\Models;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Isotopes\Profiler\Contracts\ClearableRepository;
use Isotopes\Profiler\Contracts\EntriesRepository;
use Isotopes\Profiler\Contracts\PrunableRepository;
use Isotopes\Profiler\Contracts\TerminableRepository;
use Isotopes\Profiler\Entry\EntryResult;
use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Entry\EntryUpdate;
use Isotopes\Profiler\Entry\IncomingEntry;
use Illuminate\Database\Query\Builder;
use Throwable;

/**
 * Class DatabaseEntriesRepository
 * @package Isotopes\Profiler\Models
 */
class DatabaseEntriesRepository implements EntriesRepository, ClearableRepository, PrunableRepository, TerminableRepository
{
    /**
     * The database connection name that should be used.
     *
     * @var string
     */
    protected $connection;

    /**
     * The number of entries that will be inserted at once into the database.
     *
     * @var int
     */
    protected $chunkSize = 1000;

    /**
     * The tags currently being monitored.
     *
     * @var array|null
     */
    protected $monitoredTags;

    /**
     * DatabaseEntriesRepository constructor.
     *
     * @param string $connection
     * @param int|null $chunkSize
     * @return void
     */
    public function __construct(string $connection, int $chunkSize = null)
    {
        $this->connection = $connection;

        if ($chunkSize) {
            $this->chunkSize = $chunkSize;
        }
    }

    /**
     * Clear all entries.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->collection('profiler_entries')->delete();
        $this->collection('profiler_monitoring')->delete();
    }

    public function find($id): EntryResult
    {
        $entry = EntryModel::on($this->connection)->where('uuid', $id)->firstOrFail();

        $tags = $this->collection('profiler_entries_tags')
            ->where('entry_uuid', $id)
            ->pluck('tag')
            ->all();

        return new EntryResult(
            $entry->uuid,
            null,
            $entry->batch_id,
            $entry->type,
            $entry->family_hash,
            $entry->content,
            $entry->created_at,
            $tags
        );
    }

    /**
     * Return all the entries of a given type.
     *
     * @param string|null $type
     * @param EntryQueryOptions $options
     * @return Collection|EntryResult[]
     */
    public function get($type, EntryQueryOptions $options)
    {
        return EntryModel::on($this->connection)
            ->withProfilerOptions($type, $options)
            ->take($options->limit)
            ->orderByDesc('sequence')
            ->orderBy('created_at', 'desc')
            ->get()->reject(static function ($entry) {
                return !is_array($entry->content);
            })
            ->map(static function ($entry) {
                return new EntryResult(
                    $entry->uuid,
                    $entry->sequence,
                    $entry->batch_id,
                    $entry->type,
                    $entry->family_hash,
                    $entry->content,
                    $entry->created_at,
                    []
                );
            })
            ->values();
    }

    /**
     * Store the given array of entries.
     *
     * @param Collection|IncomingEntry[] $entries
     * @return void
     */
    public function store(Collection $entries)
    {
        if ($entries->isEmpty()) {
            return;
        }

        [$exceptions, $entries] = $entries->partition->isException();

        $this->storeExceptions($exceptions);

        $collection = $this->collection('profiler_entries');

        $entries->chunk($this->chunkSize)->each(static function ($chunked) use ($collection) {
            $collection->insert($chunked->map(static function ($entry) {
                $entry->content = json_encode($entry->content);

                return $entry->toArray();
            })->toArray());
        });

        $this->storeTags($entries->pluck('tags', 'uuid'));
    }

    /**
     * Store the given entry updates.
     *
     * @param Collection|EntryUpdate[] $updates
     * @return void
     */
    public function update(Collection $updates)
    {
        foreach ($updates as $update) {
            $entry = $this->collection('profiler_entries')
                ->where('uuid', $update->uuid)
                ->where('type', $update->type)
                ->first();

            if (!$entry) {
                continue;
            }

            $content = json_encode(array_merge(
                json_decode($entry->content, true) ?: [], $update->changes
            ));

            $this->collection('profiler_entries')
                ->where('uuid', $update->uuid)
                ->where('type', $update->type)
                ->update(['content' => $content]);

            $this->updateTags($update);
        }
    }

    /**
     * Load the monitored tags from storage.
     *
     * @return void
     */
    public function loadMonitoredTags()
    {
        try {
            $this->monitoredTags = $this->monitoring();
        } catch (Throwable $e) {
            $this->monitoredTags = [];
        }
    }

    /**
     * Determine if any of the given tags are currently being monitored.
     *
     * @param array $tags
     * @return bool
     */
    public function isMonitoring(array $tags)
    {
        if ($this->monitoredTags === null) {
            $this->loadMonitoredTags();
        }

        return count(array_intersect($tags, $this->monitoredTags)) > 0;
    }

    /**
     * Get the list of tags currently being monitored.
     *
     * @return array
     */
    public function monitoring()
    {
        return $this->collection('profiler_monitoring')->pluck('tag')->all();
    }

    /**
     * Begin monitoring the given list of tags.
     *
     * @param array $tags
     * @return void
     */
    public function monitor(array $tags)
    {
        $tags = array_diff($tags, $this->monitoring());

        if (empty($tags)) {
            return;
        }

        $this->collection('profiler_monitoring')
            ->insert(collect($tags)
                ->mapWithKeys(static function ($tag) {
                    return ['tag' => $tag];
                })->all());
    }

    /**
     * Stop monitoring the given list of tags.
     *
     * @param array $tags
     * @return void
     */
    public function stopMonitoring(array $tags)
    {
        $this->collection('profiler_monitoring')->whereIn('tag', $tags)->delete();
    }

    /**
     * Prune all the entries older than the given date.
     *
     * @param DateTimeInterface $before
     * @return int|void
     */
    public function prune(DateTimeInterface $before)
    {
        $query = $this->collection('profiler_entries')
            ->where('created_at', '<', $before);

        $totalDeleted = 0;

        do {
            $deleted = $query->take($this->chunkSize)->delete();

            $totalDeleted += $deleted;
        } while ($deleted !== 0);

        return $totalDeleted;
    }

    /**
     * Perform any clean-up tasks needed after storing Profiler entries.
     *
     * @return void
     */
    public function terminate(): void
    {
        $this->monitoredTags = null;
    }

    /**
     * Get a query builder instance for the given collection.
     *
     * @param $collection
     * @return Builder
     */
    protected function collection($collection)
    {
        return DB::connection($this->connection)->table($collection);
    }

    /**
     * Counts the occurrences of an exception.
     *
     * @param IncomingEntry $entry
     * @return int
     */
    protected function countExceptionOccurrences(IncomingEntry $entry)
    {
        return $this->collection('profiler_entries')
            ->where('type', EntryType::EXCEPTION)
            ->where('family_hash', $entry->familyHash())
            ->count();
    }

    /**
     * Store the given array of exception entries.
     *
     * @param Collection|IncomingEntry[] $collection
     * @return void
     */
    protected function storeExceptions(Collection $exceptions)
    {
        $exceptions->chunk($this->chunkSize)->each(function ($chunked) {
            $this->collection('profiler_entries')->insert($chunked->map(function ($exception) {
                $occurrences = $this->countExceptionOccurrences($exception);

                $this->collection('profiler_entries')
                    ->where('type', EntryType::EXCEPTION)
                    ->where('family_hash', $exception->familyHash())
                    ->update(['should_display_on_index' => false]);

                return array_merge($exception->toArray(), [
                    'family_hash' => $exception->familyHash(),
                    'content' => json_encode(array_merge(
                        $exception->content, ['occurrences' => $occurrences + 1]
                    )),
                ]);
            })->toArray());
        });

        $this->storeTags($exceptions->pluck('tags', 'uuid'));
    }

    /**
     * Store tags for the given entries.
     *
     * @param Collection $results
     * @return void
     */
    protected function storeTags(Collection $results)
    {
        if ($results->count()) {
            $results->chunk($this->chunkSize)->each(function ($chunked) {
                $builder = $this->collection('profiler_entries_tags');
                foreach ($chunked as $uuid => $tags) {
                    if (count($tags)) {
                        $collection = collect($tags)->map(static function ($tag) use ($uuid) {
                            return [
                                'entry_uuid' => $uuid,
                                'tag' => $tag,
                            ];
                        })->all();

                        $builder->insert($collection);
                    }
                }

                /*$this->collection('profiler_entries_tags')->insert($chunked->flatMap(static function ($tags, $uuid) {
                    return collect($tags)->map(static function ($tag) use ($uuid) {
                        return [
                            'entry_uuid' => $uuid,
                            'tag' => $tag,
                        ];
                    });
                })->all());*/
            });
        }
    }

    /**
     * Update tags of the given entry.
     *
     * @param EntryUpdate $entry
     * @return void
     */
    protected function updateTags(EntryUpdate $entry)
    {
        if (!empty($entry->tagsChanges['added'])) {
            $this->collection('profiler_entries_tags')->insert(
                collect($entry->tagsChanges['added'])->map(static function ($tag) use ($entry) {
                    return [
                        'entry_uuid' => $entry->uuid,
                        'tag' => $tag,
                    ];
                })->toArray()
            );
        }

        collect($entry->tagsChanges['removed'])->each(function ($tag) use ($entry) {
            $this->collection('profiler_entries_tags')
                ->where('entry_uuid', $entry->uuid)
                ->where('tag', $tag)
                ->delete();
        });
    }
}
