<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Isotopes\Profiler\Entry\IncomingDumpEntry;
use Isotopes\Profiler\Profiler;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

class DumpWatcher extends Watcher
{
    /**
     * The cache factory implementation.
     *
     * @var CacheFactory
     */
    protected $cache;

    /**
     * Create a new watcher instance.
     *
     * @param CacheFactory $cache
     * @param  array  $options
     * @return void
     */
    public function __construct(CacheFactory $cache, array $options = [])
    {
        parent::__construct($options);

        $this->cache = $cache;
    }

    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app)
    {
        if (! $this->cache->get('profiler:dump-watcher')) {
            return;
        }

        $htmlDumper = new HtmlDumper();
        $htmlDumper->setDumpHeader('');

        VarDumper::setHandler(function ($var) use ($htmlDumper) {
            $this->recordDump($htmlDumper->dump(
                (new VarCloner)->cloneVar($var), true
            ));
        });
    }

    /**
     * Record a dumped variable.
     *
     * @param  string  $dump
     * @return void
     */
    public function recordDump($dump)
    {
        Profiler::recordDump(
            IncomingDumpEntry::make(['dump' => $dump])
        );
    }
}
