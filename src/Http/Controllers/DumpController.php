<?php

namespace Isotopes\Profiler\Http\Controllers;

use Exception;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Isotopes\Profiler\Contracts\EntriesRepository;
use Isotopes\Profiler\Entry\EntryType;
use Isotopes\Profiler\Models\EntryQueryOptions;
use Isotopes\Profiler\Watchers\DumpWatcher;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class DumpController extends ProfilerController
{
    /**
     * The cache repository implementation.
     *
     * @var CacheRepository
     */
    protected $cache;

    /**
     * Create a new controller instance.
     *
     * @param CacheRepository $cache
     * @return void
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

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
        $this->cache->put('profiler:dump-watcher', true, now()->addSeconds(4));

        return response()->json([
            'dump' => (new HtmlDumper())->dump((new VarCloner)->cloneVar(true), true),
            'entries' => $storage->get(
                $this->entryType(),
                EntryQueryOptions::fromRequest($request)
            ),
            'status' => $this->status(),
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
        if ($this->cache->getStore() instanceof ArrayStore) {
            return 'wrong-cache';
        }

        return parent::status();
    }

    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType(): string
    {
        return EntryType::DUMP;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher(): string
    {
        return DumpWatcher::class;
    }
}
