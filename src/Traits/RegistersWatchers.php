<?php

namespace Isotopes\Profiler\Traits;

use Illuminate\Foundation\Application;

trait RegistersWatchers
{
    /**
     * Class names of the registered watchers.
     *
     * @var array
     */
    protected static $watchers = [];

    /**
     * Determine if a given watcher has been registered.
     *
     * @param $class
     * @return bool
     */
    public static function hasWatcher($class): bool
    {
        return in_array($class, static::$watchers, true);
    }

    /**
     * Register the configured Telescope watchers.
     *
     * @param Application $application
     * @return void
     */
    protected static function registerWatchers(Application $application): void
    {
        $watchers = config('profiler.watchers');

        foreach ($watchers as $key => $watcher) {
            if (is_string($key) && $watcher === false) {
                continue;
            }

            if (is_array($watcher) && !($watcher['enabled'] ?? true)) {
                continue;
            }

            $watcher = $application->make(is_string($key) ? $key : $watcher, [
                'options' => is_array($watcher) ? $watcher : []
            ]);

            static::$watchers[] = get_class($watcher);

            $watcher->register($application);
        }
    }
}
