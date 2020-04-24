<?php

namespace Isotopes\Profiler\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profiler:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all the Profiler resources.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->comment('Publishing Profiler Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'profiler-provider']);

        $this->comment('Publishing Profiler Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'profiler-assets']);

        $this->comment('Publishing Profiler Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'profiler-config']);

        $this->info('Profiler scaffolding installed successfully.');
    }
}
