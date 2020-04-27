<?php

namespace Isotopes\Profiler;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use stdClass;

/**
 * Class ExtractTags
 * @package Isotopes\Profiler
 */
class ExtractTags
{
    /**
     * Get the tags for the given object.
     *
     * @param $target
     * @return array|Collection|mixed
     * @throws ReflectionException
     */
    public static function from($target)
    {
        if ($tags = static::explicitTags([$target])) {
            return $tags;
        }

        return static::modelsFor([$target])->map(static function ($model) {
            return FormatModel::given($model);
        });
    }

    /**
     * Determine the tags for the given job.
     *
     * @param mixed $job
     * @return array|mixed|null
     */
    public static function fromJob($job)
    {
        if ($tags = static::extractExplicitTags($job)) {
            return $tags;
        }

        try {
            return static::modelsFor(static::targetsFor($job))->map(static function ($model) {
                return FormatModel::given($model);
            })->all();
        } catch (ReflectionException $e) {
        }
    }

    /**
     * Determine the tags for the given array.
     *
     * @param array $data
     * @return array
     */
    public static function fromArray(array $data)
    {
        $models = collect($data)->map(static function ($value) {
            if ($value instanceof Model) {
                return [$value];
            }

            return $value->flatten();
        })->collapse()->filter();

        return $models->map(static function ($model) {
            return FormatModel::given($model);
        })->all();
    }

    /**
     * Determine tags for the given job.
     *
     * @param array $targets
     * @return array|mixed
     */
    protected static function explicitTags(array $targets)
    {
        return collect($targets)->map(static function ($target) {
            return method_exists($target, 'tags') ? $target->tags() : [];
        })->collapse()->unique()->all();
    }

    /**
     * Get the models from the given object.
     *
     * @param array $targets
     * @return Collection
     * @throws ReflectionException
     */
    protected static function modelsFor(array $targets)
    {
        $models = [];

        foreach ($targets as $target) {
            $models[] = collect(
                (new ReflectionClass($target))->getProperties()
            )->map(static function (ReflectionProperty $property) use ($target) {
                $property->setAccessible(true);

                $value = $property->getValue($target);

                if ($value instanceof Model) {
                    return [$value];
                } elseif ($value instanceof EloquentCollection) {
                    return $value->flatten();
                }

            })->collapse()->filter()->all();
        }

        return collect(Arr::collapse($models))->unique();
    }

    /**
     * Extract tags from job object.
     *
     * @param $job
     * @return array|mixed|null
     */
    protected static function extractExplicitTags($job)
    {
        return $job instanceof CallQueuedListener
            ? static::tagsForListener($job)
            : static::explicitTags(static::targetsFor($job));
    }

    /**
     * Extract tags from job object.
     *
     * @param $job
     * @return array
     */
    protected static function tagsForListener($job)
    {
        try {
            return collect(
                [static::extractListener($job), static::extractEvent($job)]
            )->map(static function ($job) {
                return static::from($job);
            })->collapse()->unique()->toArray();
        } catch (ReflectionException $e) {
        }
    }

    /**
     * Extract the listener from a queued job.
     *
     * @param $job
     * @return object
     * @throws ReflectionException
     */
    protected static function extractListener($job)
    {
        return (new ReflectionClass($job->class))->newInstanceWithoutConstructor();
    }

    /**
     * Extract the event from a queued job.
     *
     * @param mixed $job
     * @return mixed|stdClass
     */
    protected static function extractEvent($job)
    {
        return isset($job->data[0]) && is_object($job->data[0]) ? $job->data[0] : new stdClass;
    }

    /**
     * Get the actual target for the given job.
     *
     * @param $job
     * @return array|stdClass[]
     */
    protected static function targetsFor($job)
    {
        switch (true) {
            case $job instanceof BroadcastEvent:
                return [$job->event];
            case $job instanceof CallQueuedListener:
                return [static::extractEvent($job)];
            case $job instanceof SendQueuedMailable:
                return [$job->mailable];
            case $job instanceof SendQueuedNotifications:
                return [$job->notification];
            default:
                return [$job];
        }
    }
}
