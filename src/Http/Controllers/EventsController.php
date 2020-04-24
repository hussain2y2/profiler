<?php

namespace Isotopes\Profiler\Http\Controllers;

use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Watchers\EventWatcher;

class EventsController extends ProfilerController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType(): string
    {
        return EntryType::EVENT;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher(): string
    {
        return EventWatcher::class;
    }
}
