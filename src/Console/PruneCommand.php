<?php

namespace Isotopes\Profiler\Console;

use Illuminate\Console\Command;
use Isotopes\Profiler\Contracts\PrunableRepository;

class PruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profiler:prune {--hours=24 : The number of hours to retain Profiler data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale entries from the Profiler database.';

    /**
     * Execute the console command.
     *
     * @param PrunableRepository $repository
     * @return void
     */
    public function handle(PrunableRepository $repository): void
    {
        $this->info($repository->prune(now()->subHours($this->option('hours'))).' entries pruned.');
    }
}
