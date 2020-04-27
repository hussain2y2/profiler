<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Isotopes\Profiler\FormatModel;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;

class GateWatcher extends Watcher
{
    use FetchesStackTrace;

    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app)
    {
        Gate::after([$this, 'recordGateCheck']);
    }

    /**
     * Record a gate check.
     *
     * @param Authenticatable|null  $user
     * @param  string  $ability
     * @param  bool  $result
     * @param  array  $arguments
     * @return bool
     */
    public function recordGateCheck(?Authenticatable $user, $ability, $result, $arguments)
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($ability)) {
            return;
        }

        $caller = $this->getCallerFromStackTrace();

        Profiler::recordGate(IncomingEntry::make([
            'ability'   => $ability,
            'result'    => $result ? 'allowed' : 'denied',
            'arguments' => $this->formatArguments($arguments),
            'file'      => $caller['file'],
            'line'      => $caller['line'],
        ]));

        return $result;
    }

    /**
     * Determine if the ability should be ignored.
     *
     * @param  string  $ability
     * @return bool
     */
    private function shouldIgnore($ability)
    {
        return Str::is($this->options['ignore_abilities'] ?? [], $ability);
    }

    /**
     * Format the given arguments.
     *
     * @param  array  $arguments
     * @return array
     */
    private function formatArguments($arguments)
    {
        return collect($arguments)->map(function ($argument) {
            return $argument instanceof Model ? FormatModel::given($argument) : $argument;
        })->toArray();
    }
}
