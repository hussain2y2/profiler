<?php

namespace Isotopes\Profiler\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Isotopes\Profiler\Contracts\EntriesRepository;
use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Models\EntryQueryOptions;
use Isotopes\Profiler\Watchers\JobWatcher;

class QueueController extends ProfilerController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType(): string
    {
        return EntryType::JOB;
    }

    /**
     * Get an entry with the given ID.
     *
     * @param EntriesRepository $storage
     * @param int $id
     * @return JsonResponse
     */
    public function show(EntriesRepository $storage, $id): JsonResponse
    {
        $entry = $storage->find($id);

        return response()->json([
            'entry' => $entry,
            'batch' => isset($entry->content['updated_batch_id'])
                            ? $storage->get(null, EntryQueryOptions::forBatchId($entry->content['updated_batch_id']))
                            : null,
        ]);
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher(): string
    {
        return JobWatcher::class;
    }
}
