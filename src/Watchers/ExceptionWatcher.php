<?php

namespace Isotopes\Profiler\Watchers;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Isotopes\Profiler\ExceptionContext;
use Isotopes\Profiler\ExtractTags;
use Isotopes\Profiler\Entry\IncomingExceptionEntry;
use Isotopes\Profiler\Profiler;
use ReflectionException;

class ExceptionWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app): void
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordException']);
    }

    /**
     * Record an exception was logged.
     *
     * @param MessageLogged $event
     * @return void
     * @throws ReflectionException
     */
    public function recordException(MessageLogged $event): void
    {
        if (! Profiler::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        $exception = $event->context['exception'];

        $trace = collect($exception->getTrace())->map(static function ($item) {
            return Arr::only($item, ['file', 'line']);
        })->toArray();

        Profiler::recordException(
            IncomingExceptionEntry::make($exception, [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $trace,
                'line_preview' => ExceptionContext::get($exception),
            ])->tags($this->tags($event))
        );
    }

    /**
     * Extract the tags for the given event.
     *
     * @param MessageLogged $event
     * @return array
     * @throws ReflectionException
     */
    protected function tags($event): array
    {
        return array_merge(ExtractTags::from($event->context['exception'])->toArray(),
            $event->context['profiler'] ?? []
        );
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event): bool
    {
        return ! isset($event->context['exception']) || ! $event->context['exception'] instanceof Exception;
    }
}
