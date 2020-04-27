<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;

class CacheWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(CacheHit::class, [$this, 'recordCacheHit']);
        $app['events']->listen(CacheMissed::class, [$this, 'recordCacheMissed']);

        $app['events']->listen(KeyWritten::class, [$this, 'recordKeyWritten']);
        $app['events']->listen(KeyForgotten::class, [$this, 'recordKeyForgotten']);
    }

    /**
     * Record a cache key found.
     *
     * @param CacheHit $event
     * @return void
     */
    public function recordCacheHit(CacheHit $event)
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Profiler::recordCache(IncomingEntry::make([
            'type' => 'hit',
            'key' => $event->key,
            'value' => $event->value,
        ]));
    }

    /**
     * Record a missing cache key.
     *
     * @param CacheMissed $event
     * @return void
     */
    public function recordCacheMissed(CacheMissed $event)
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Profiler::recordCache(IncomingEntry::make([
            'type' => 'missed',
            'key' => $event->key,
        ]));
    }

    /**
     * Record a cache key updated.
     *
     * @param KeyWritten $event
     * @return void
     */
    public function recordKeyWritten(KeyWritten $event)
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Profiler::recordCache(IncomingEntry::make([
            'type' => 'set',
            'key' => $event->key,
            'value' => $event->value,
            'expiration' => $this->formatExpiration($event),
        ]));
    }

    /**
     * Record a cache key forgotten/removed.
     *
     * @param KeyForgotten $event
     * @return void
     */
    public function recordKeyForgotten(KeyForgotten $event)
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Profiler::recordCache(IncomingEntry::make([
            'type' => 'forget',
            'key' => $event->key,
        ]));
    }

    /**
     * @param KeyWritten $event
     * @return mixed
     */
    protected function formatExpiration(KeyWritten $event)
    {
        return property_exists($event, 'seconds')
                ? $event->seconds : $event->minutes * 60;
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        return Str::is([
            'illuminate:queue:restart',
            'framework/schedule*',
            'profiler:*',
        ], $event->key);
    }
}
