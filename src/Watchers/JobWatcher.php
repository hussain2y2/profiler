<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Queue;
use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Entry\EntryUpdate;
use Isotopes\Profiler\ExceptionContext;
use Isotopes\Profiler\ExtractProperties;
use Isotopes\Profiler\ExtractTags;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;
use ReflectionException;

class JobWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app): void
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['profiler_uuid' => optional($this->recordJob($connection, $queue, $payload))->uuid];
        });

        $app['events']->listen(JobProcessed::class, [$this, 'recordProcessedJob']);
        $app['events']->listen(JobFailed::class, [$this, 'recordFailedJob']);
    }

    /**
     * Record a job being created.
     *
     * @param string $connection
     * @param string $queue
     * @param array $payload
     * @return IncomingEntry|void
     */
    public function recordJob($connection, $queue, array $payload)
    {
        if (! Profiler::isRecording()) {
            return;
        }

        $content = array_merge([
            'status' => 'pending',
        ], $this->defaultJobData($connection, $queue, $payload, $this->data($payload)));

        Profiler::recordJob(
            $entry = IncomingEntry::make($content)->tags($this->tags($payload))
        );

        return $entry;
    }

    /**
     * Record a queued job processed.
     *
     * @param JobProcessed $event
     * @return void
     */
    public function recordProcessedJob(JobProcessed $event): void
    {
        if (! Profiler::isRecording()) {
            return;
        }

        $uuid = $event->job->payload()['profiler_uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        Profiler::recordUpdate(EntryUpdate::make(
            $uuid, EntryType::JOB, ['status' => 'processed']
        ));
    }

    /**
     * Record a queue job has failed.
     *
     * @param JobFailed $event
     * @return void
     */
    public function recordFailedJob(JobFailed $event): void
    {
        if (! Profiler::isRecording()) {
            return;
        }

        $uuid = $event->job->payload()['profiler_uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        Profiler::recordUpdate(EntryUpdate::make(
            $uuid, EntryType::JOB, [
                'status'    => 'failed',
                'exception' => [
                    'message'       => $event->exception->getMessage(),
                    'trace'         => $event->exception->getTrace(),
                    'line'          => $event->exception->getLine(),
                    'line_preview'  => ExceptionContext::get($event->exception),
                ],
            ]
        )->addTags(['failed']));
    }

    /**
     * Get the default entry data for the given job.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  array  $payload
     * @param  array  $data
     * @return array
     */
    protected function defaultJobData($connection, $queue, array $payload, array $data): array
    {
        return [
            'connection' => $connection,
            'queue'      => $queue,
            'name'       => $payload['displayName'],
            'tries'      => $payload['maxTries'],
            'timeout'    => $payload['timeout'],
            'data'       => $data,
        ];
    }

    /**
     * Extract the job "data" from the job payload.
     *
     * @param array $payload
     * @return array
     * @throws ReflectionException
     */
    protected function data(array $payload)
    {
        if (! isset($payload['data']['command'])) {
            return $payload['data'];
        }

        return ExtractProperties::from(
            $payload['data']['command']
        );
    }

    /**
     * Extract the tags from the job payload.
     *
     * @param  array  $payload
     * @return array
     */
    protected function tags(array $payload): array
    {
        if (! isset($payload['data']['command'])) {
            return [];
        }

        return ExtractTags::fromJob(
            $payload['data']['command']
        );
    }
}
