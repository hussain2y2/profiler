<?php

namespace Isotopes\Profiler\Http\Controllers;

use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Watchers\NotificationWatcher;

class NotificationsController extends ProfilerController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType(): string
    {
        return EntryType::NOTIFICATION;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher(): string
    {
        return NotificationWatcher::class;
    }
}
