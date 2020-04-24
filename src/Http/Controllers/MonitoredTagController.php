<?php

namespace Isotopes\Profiler\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Isotopes\Profiler\Contracts\EntriesRepository;

class MonitoredTagController extends Controller
{
    /**
     * The entry repository implementation.
     *
     * @var EntriesRepository
     */
    protected $entries;

    /**
     * Create a new controller instance.
     *
     * @param EntriesRepository $entries
     */
    public function __construct(EntriesRepository $entries)
    {
        $this->entries = $entries;
    }

    /**
     * Get all the tags being monitored.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'tags' => $this->entries->monitoring(),
        ]);
    }

    /**
     * Begin monitoring the given tag.
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request): void
    {
        $this->entries->monitor([$request->tag]);
    }

    /**
     * Stop monitoring the given tag.
     *
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        $this->entries->stopMonitoring([$request->tag]);
    }
}
