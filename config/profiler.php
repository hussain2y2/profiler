<?php

use Isotopes\Profiler\Http\Middleware\Authorize;
use Isotopes\Profiler\Watchers;

return [
    /*
    |--------------------------------------------------------------------------
    | Profiler Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Profiler will be accessible from. If the
    | setting is null, Profiler will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */
    'domain' => env('PROFILER_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Profiler Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the storage driver that will
    | be used to store Profiler's data. In addition, you may set any
    | custom options as needed by the particular driver you choose.
    |
    */
    'driver' => env('PROFILER_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Profiler Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Profiler will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */
    'path' => env('PROFILER_PATH', 'profiler'),

    /*
    |--------------------------------------------------------------------------
    | Profiler Watchers
    |--------------------------------------------------------------------------
    |
    | The following array lists the "watchers" that will be registered with
    | Telescope. The watchers gather the application's profile data when
    | a request or task is executed. Feel free to customize this list.
    |
    */
    'watchers' => [
        Watchers\CacheWatcher::class => env('PROFILER_CACHE_WATCHER', true),
        Watchers\CommandWatcher::class => [
            'enabled' => env('PROFILER_COMMAND_WATCHER', true),
            'ignore' => [],
        ],
        Watchers\DumpWatcher::class => env('PROFILER_DUMP_WATCHER', true),
        Watchers\EventWatcher::class => [
            'enabled' => env('PROFILER_EVENT_WATCHER', true),
            'ignore' => [],
        ],
        Watchers\ExceptionWatcher::class => env('PROFILER_EXCEPTION_WATCHER', true),
        Watchers\JobWatcher::class => env('PROFILER_JOB_WATCHER', true),
        Watchers\LogWatcher::class => env('PROFILER_LOG_WATCHER', true),
        Watchers\MailWatcher::class => env('PROFILER_MAIL_WATCHER', true),
        Watchers\ModelWatcher::class => [
            'enabled' => env('PROFILER_MODEL_WATCHER', true),
            'events' => ['eloquent.*'],
        ],
        Watchers\NotificationWatcher::class => env('PROFILER_NOTIFICATION_WATCHER', true),
        Watchers\QueryWatcher::class => [
            'enabled' => env('PROFILER_QUERY_WATCHER', true),
            'ignore_packages' => true,
            'slow' => 100,
        ],
        Watchers\RedisWatcher::class => env('PROFILER_REDIS_WATCHER', true),
        Watchers\RequestWatcher::class => [
            'enabled' => env('PROFILER_REQUEST_WATCHER', true),
            'size_limit' => env('PROFILER_RESPONSE_SIZE_LIMIT', 64),
        ],
        Watchers\GateWatcher::class => [
            'enabled' => env('PROFILER_GATE_WATCHER', true),
            'ignore_abilities' => [],
            'ignore_packages' => true,
        ],
        Watchers\ScheduleWatcher::class => env('PROFILER_SCHEDULE_WATCHER', true),
        Watchers\ViewWatcher::class => env('PROFILER_VIEW_WATCHER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Profiler Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable all Profiler watchers regardless
    | of their individual configuration, which simply provides a single
    | and convenient way to enable or disable Profiler data storage.
    |
    */
    'enabled' => env('PROFILER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Ignored Paths & Commands
    |--------------------------------------------------------------------------
    |
    | The following array lists the URI paths and Artisan commands that will
    | not be watched by Profiler. In addition to this list, some Laravel
    | commands, like migrations and queue commands, are always ignored.
    |
    */
    'ignore_commands' => [

    ],

    'ignore_paths' => [

    ],
    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mongodb'),
            'chunk' => 1000,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Profiler Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Profiler route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */
    'middleware' => [
        'web',
        Authorize::class,
    ],
];
