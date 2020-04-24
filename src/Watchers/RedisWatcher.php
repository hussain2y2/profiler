<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Redis\Events\CommandExecuted;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;

class RedisWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app): void
    {
        $app['events']->listen(CommandExecuted::class, [$this, 'recordCommand']);

        foreach ((array) $app['redis']->connections() as $connection) {
            $connection->setEventDispatcher($app['events']);
        }

        $app['redis']->enableEvents();
    }

    /**
     * Record a Redis command was executed.
     *
     * @param CommandExecuted $event
     * @return void
     */
    public function recordCommand(CommandExecuted $event): void
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Profiler::recordRedis(IncomingEntry::make([
            'connection' => $event->connectionName,
            'command'    => $this->formatCommand($event->command, $event->parameters),
            'time'       => number_format($event->time, 2),
        ]));
    }

    /**
     * Format the given Redis command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return string
     */
    private function formatCommand($command, $parameters): string
    {
        $parameters = collect($parameters)->map(function ($parameter) {
            if (is_array($parameter)) {
                return collect($parameter)->map(function ($value, $key) {
                    return is_int($key) ? $value : "{$key} {$value}";
                })->implode(' ');
            }

            return $parameter;
        })->implode(' ');

        return "{$command} {$parameters}";
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event): bool
    {
        return in_array($event->command, [
            'pipeline', 'transaction',
        ]);
    }
}
