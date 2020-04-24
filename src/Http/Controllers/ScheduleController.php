<?php

namespace Isotopes\Profiler\Http\Controllers;

use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Watchers\ScheduleWatcher;

class ScheduleController extends ProfilerController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType(): string
    {
        return EntryType::SCHEDULED_TASK;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher(): string
    {
        return ScheduleWatcher::class;
    }
}
