<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Foundation\Application;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;

class CommandWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app): void
    {
        $app['events']->listen(CommandFinished::class, [$this, 'recordCommand']);
    }

    /**
     * Record an Artisan command executed.
     *
     * @param CommandFinished $event
     * @return void
     */
    public function recordCommand(CommandFinished $event): void
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Profiler::recordCommand(IncomingEntry::make([
            'command' => $event->command ?? $event->input->getArguments()['command'] ?? 'default',
            'exit_code' => $event->exitCode,
            'arguments' => $event->input->getArguments(),
            'options' => $event->input->getOptions(),
        ]));
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event): bool
    {
        return in_array($event->command, array_merge($this->options['ignore'] ?? [], [
            'schedule:run',
            'schedule:finish',
            'package:discover',
        ]), true);
    }
}
