<?php

namespace Isotopes\Profiler\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Isotopes\Profiler\Contracts\EntriesRepository;
use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Entry\EntryUpdate;
use Isotopes\Profiler\Models\EntryQueryOptions;
use Isotopes\Profiler\Watchers\ExceptionWatcher;

class ExceptionController extends ProfilerController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType(): string
    {
        return EntryType::EXCEPTION;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher(): string
    {
        return ExceptionWatcher::class;
    }

    /**
     * Update an entry with the given ID.
     *
     * @param EntriesRepository $storage
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(EntriesRepository $storage, Request $request, $id): JsonResponse
    {
        $entry = $storage->find($id);

        if ($request->input('resolved_at') === 'now') {
            $update = new EntryUpdate($entry->id, $entry->type, [
                'resolved_at' => Carbon::now()->toDateTimeString(),
            ]);

            $storage->update(collect([$update]));

            // Reload entry
            $entry = $storage->find($id);
        }

        return response()->json([
            'entry' => $entry,
            'batch' => $storage->get(null, EntryQueryOptions::forBatchId($entry->batchId)->limit(-1)),
        ]);
    }
}
