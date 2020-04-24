<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;

class ScheduleWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app): void
    {
        $app['events']->listen(CommandStarting::class, [$this, 'recordCommand']);
    }

    /**
     * Record a scheduled command was executed.
     *
     * @param  \Illuminate\Console\Events\CommandStarting  $event
     * @return void
     */
    public function recordCommand(CommandStarting $event): void
    {
        if (($event->command !== 'schedule:run' && $event->command !== 'schedule:finish') || ! Profiler::isRecording()) {
            return;
        }

        collect(app(Schedule::class)->events())->each(function ($event) {
            $event->then(function () use ($event) {
                Profiler::recordScheduledCommand(IncomingEntry::make([
                    'command'       => $event instanceof CallbackEvent ? 'Closure' : $event->command,
                    'description'   => $event->description,
                    'expression'    => $event->expression,
                    'timezone'      => $event->timezone,
                    'user'          => $event->user,
                    'output'        => $this->getEventOutput($event),
                ]));
            });
        });
    }

    /**
     * Get the output for the scheduled event.
     *
     * @param Event $event
     * @return string|null
     */
    protected function getEventOutput(Event $event)
    {
        if (! $event->output || $event->shouldAppendOutput  || $event->output === $event->getDefaultOutput()|| ! file_exists($event->output)) {
            return '';
        }

        return trim(file_get_contents($event->output));
    }
}
