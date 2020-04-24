<?php

namespace Isotopes\Profiler\Console;

use Illuminate\Console\Command;
use Isotopes\Profiler\Contracts\ClearableRepository;

class ClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profiler:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all entries from Profiler.';

    /**
     * Execute the console command.
     *
     * @param ClearableRepository $storage
     * @return void
     */
    public function handle(ClearableRepository $storage): void
    {
        $storage->clear();
        $this->info('Profiler entries cleared!');
    }
}
