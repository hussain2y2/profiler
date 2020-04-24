<?php

namespace Isotopes\Profiler\Http\Controllers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Routing\Controller;
use Psr\SimpleCache\InvalidArgumentException;

class RecordingController extends Controller
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
     * Toggle recording.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function toggle(): void
    {
        if ($this->cache->get('profiler:pause-recording')) {
            $this->cache->forget('profiler:pause-recording');
        } else {
            $this->cache->put('profiler:pause-recording', true, now()->addDays(30));
        }
    }
}
