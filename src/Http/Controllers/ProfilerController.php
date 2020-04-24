<?php

namespace Isotopes\Profiler\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Isotopes\Profiler\Contracts\EntriesRepository;
use Isotopes\Profiler\Models\EntryQueryOptions;

abstract class ProfilerController extends Controller
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    abstract protected function entryType(): string;

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    abstract protected function watcher(): string;

    /**
     * List the entries of the given type.
     *
     * @param Request $request
     * @param EntriesRepository $storage
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request, EntriesRepository $storage): JsonResponse
    {
        return response()->json([
            'entries' => $storage->get(
                $this->entryType(),
                EntryQueryOptions::fromRequest($request)
            ),
            'status' => $this->status(),
        ]);
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
            'batch' => $storage->get(null, EntryQueryOptions::forBatchId($entry->batchId)->limit(-1)),
        ]);
    }

    /**
     * Determine the watcher recording status.
     *
     * @return string
     * @throws Exception
     */
    protected function status(): string
    {
        if (! config('profiler.enabled', false)) {
            return 'disabled';
        }

        if (cache('profiler:pause-recording', false)) {
            return 'paused';
        }

        $watcher = config('profiler.watchers.'.$this->watcher());

        if (! $watcher || (isset($watcher['enabled']) && ! $watcher['enabled'])) {
            return 'off';
        }

        return 'enabled';
    }
}
