<?php

namespace Isotopes\Profiler;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProfilerApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->authorization();
    }

    /**
     * Configure the Profiler authorization services.
     *
     * @return void
     */
    protected function authorization(): void
    {
        $this->gate();

        Profiler::auth(static function (Request $request) {
          return app()->environment('local') || Gate::check('viewProfiler', [$request->user()]);
        });
    }

    /**
     * Register the Profiler gate.
     *
     * This gate determines who can access Profiler in non-local environments.
     * @return void
     */
    protected function gate(): void
    {
        Gate::define('viewProfiler', static function ($user) {
            return in_array($user->email, [
            ], true);
        });
    }
}
