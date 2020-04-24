<?php

namespace Isotopes\Profiler\Watchers;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;

class LogWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app): void
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordLog']);
    }

    /**
     * Record a message was logged.
     *
     * @param MessageLogged $event
     * @return void
     */
    public function recordLog(MessageLogged $event): void
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Profiler::recordLog(
            IncomingEntry::make([
                'level'   => $event->level,
                'message' => $event->message,
                'context' => Arr::except($event->context, ['profiler']),
            ])->tags($this->tags($event))
        );
    }

    /**
     * Extract tags from the given event.
     *
     * @param MessageLogged $event
     * @return array
     */
    private function tags($event): array
    {
        return $event->context['profiler'] ?? [];
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event): bool
    {
        return isset($event->context['exception']) && $event->context['exception'] instanceof Exception;
    }
}
