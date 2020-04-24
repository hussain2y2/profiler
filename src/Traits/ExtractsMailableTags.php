<?php

namespace Isotopes\Profiler\Traits;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Isotopes\Profiler\ExtractTags;

trait ExtractsMailableTags
{
    /**
     * Register a callback to extract mailable tags.
     *
     * @return void
     */
    protected static function registerMailableTagExtractor(): void
    {
        Mailable::buildViewDataUsing(static function ($mailable) {
            return [
                '__profiler' => ExtractTags::from($mailable),
                '__profiler_mailable' => get_class($mailable),
                '__profiler_queued' => in_array(ShouldQueue::class, class_implements($mailable))
            ];
        });
    }
}
