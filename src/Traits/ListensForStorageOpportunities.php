<?php

namespace Isotopes\Profiler\Traits;

use Illuminate\Foundation\Application;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Isotopes\Profiler\Contracts\EntriesRepository;

trait ListensForStorageOpportunities
{
    /**
     * An array indicating how many jobs are processing.
     *
     * @var array
     */
    protected static $processingJobs = [];

    /**
     * Register listeners that store the recorded Profiler entries.
     *
     * @param Application $application
     * @return void
     */
    public static function listenForStorageOpportunities(Application $application): void
    {
        static::storeEntriesBeforeTermination($application);
        static::storeEntriesAfterWorkerLoop($application);
    }

    /**
     * Store the entries in queue before the application termination.
     *
     * This handles storing entries for HTTP requests and Artisan commands.
     *
     * @param Application $application
     * @return void
     */
    protected static function storeEntriesBeforeTermination(Application $application): void
    {
        $application->terminating(static function () use ($application) {
            static::store($application[EntriesRepository::class]);
        });
    }

    /**
     * Store entries after the queue worker loops.
     *
     * @param Application $application
     * @return void
     */
    protected static function storeEntriesAfterWorkerLoop(Application $application): void
    {
        $application['events']->listen(JobProcessing::class, static function ($event) {
            if ($event->connectionName !== 'sync') {
                static::startRecording();

                static::$processingJobs[] = true;
            }
        });
    }

    /**
     * Store the recorded entries if totally done processing the current job.
     *
     * @param JobProcessed $event
     * @param Application $app
     * @return void
     */
    protected static function storeIfDoneProcessingJob(JobProcessed $event, Application $app): void
    {
        array_pop(static::$processingJobs);

        if (empty(static::$processingJobs) && $event->connectionName !== 'sync') {
            static::store($app[EntriesRepository::class]);
            static::stopRecording();
        }
    }
}
