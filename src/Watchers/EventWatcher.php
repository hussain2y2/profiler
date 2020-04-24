<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Isotopes\Profiler\ExtractProperties;
use Isotopes\Profiler\ExtractTags;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;
use ReflectionException;
use ReflectionFunction;

class EventWatcher extends Watcher
{
    use FormatsClosure;

    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app): void
    {
        $app['events']->listen('*', [$this, 'recordEvent']);
    }

    /**
     * Record an event fired.
     *
     * @param string $eventName
     * @param array $payload
     * @return void
     * @throws ReflectionException
     */
    public function recordEvent($eventName, $payload): void
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($eventName)) {
            return;
        }

        $formattedPayload = $this->extractPayload($eventName, $payload);

        Profiler::recordEvent(IncomingEntry::make([
            'name' => $eventName,
            'payload' => empty($formattedPayload) ? null : $formattedPayload,
            'listeners' => $this->formatListeners($eventName),
            'broadcast' => class_exists($eventName)
                        ? in_array(ShouldBroadcast::class, (array)class_implements($eventName), true)
                        : false,
        ])->tags(class_exists($eventName) && isset($payload[0]) ? ExtractTags::from($payload[0]) : []));
    }

    /**
     * Extract the payload and tags from the event.
     *
     * @param string $eventName
     * @param array $payload
     * @return array
     * @throws ReflectionException
     */
    protected function extractPayload($eventName, $payload): array
    {
        if (isset($payload[0]) && is_object($payload[0] && class_exists($eventName))) {
            return ExtractProperties::from($payload[0]);
        }

        return collect($payload)->map(static function ($value) {
            return is_object($value) ? [
                'class' => get_class($value),
                'properties' => json_decode(json_encode($value), true),
            ] : $value;
        })->toArray();
    }

    /**
     * Format list of event listeners.
     *
     * @param  string  $eventName
     * @return array
     */
    protected function formatListeners($eventName): array
    {
        return collect(app('events')->getListeners($eventName))
            ->map(function ($listener) {
                $listener = (new ReflectionFunction($listener))
                        ->getStaticVariables()['listener'];

                if (is_string($listener)) {
                    return Str::contains($listener, '@') ? $listener : $listener.'@handle';
                } elseif (is_array($listener)) {
                    return get_class($listener[0]).'@'.$listener[1];
                }

                return $this->formatClosureListener($listener);
            })->reject(static function ($listener) {
                return Str::contains($listener, 'Laravel\\Profiler');
            })->map(static function ($listener) {
                if (Str::contains($listener, '@')) {
                    $queued = in_array(ShouldQueue::class, class_implements(explode('@', $listener)[0]), true);
                }

                return [
                    'name' => $listener,
                    'queued' => $queued ?? false,
                ];
            })->values()->toArray();
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  string  $eventName
     * @return bool
     */
    protected function shouldIgnore($eventName): bool
    {
        return $this->eventIsIgnored($eventName) ||
            (Profiler::$ignoreFrameworkEvents && $this->eventIsFiredByTheFramework($eventName));
    }

    /**
     * Determine if the event fired internally by Laravel.
     *
     * @param  string  $eventName
     * @return bool
     */
    protected function eventIsFiredByTheFramework($eventName): bool
    {
        return Str::is(
            ['Illuminate\*', 'eloquent*', 'bootstrapped*', 'bootstrapping*', 'creating*', 'composing*'],
            $eventName
        );
    }

    /**
     * Determine if the event ignored manually.
     *
     * @param  string  $eventName
     * @return bool
     */
    protected function eventIsIgnored($eventName): bool
    {
        return Str::is($this->options['ignore'] ?? [], $eventName);
    }
}
