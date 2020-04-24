<?php

namespace Isotopes\Profiler;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Isotopes\Profiler\Console;
use Isotopes\Profiler\Contracts\ClearableRepository;
use Isotopes\Profiler\Contracts\EntriesRepository;
use Isotopes\Profiler\Contracts\PrunableRepository;
use Isotopes\Profiler\Models\DatabaseEntriesRepository;

/**
 * Class ProfilerServiceProvider
 * @package Isotopes\Profiler
 */
class ProfilerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if (! config('profiler.enabled')) {
            return;
        }

        Route::middlewareGroup('profiler', config('profiler.middleware', []));

        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerPublishing();

        Profiler::start($this->app);
        Profiler::listenForStorageOpportunities($this->app);

        $this->loadViewsFrom(
            __DIR__.'/../resources/views', 'profiler'
        );
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        });
    }

    /**
     * Get the Profiler route group configuration array.
     *
     * @return array
     */
    private function routeConfiguration(): array
    {
        return [
            'domain'      => config('profiler.domain', null),
            'namespace'   => 'Isotopes\Profiler\Http\Controllers',
            'prefix'      => config('profiler.path'),
            'middleware'  => 'profiler',
        ];
    }

    /**
     * Register the package's migrations.
     *
     * @return void
     */
    private function registerMigrations(): void
    {
        if ($this->app->runningInConsole() && $this->shouldMigrate()) {
            $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
        }
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Database/migrations' => database_path('migrations')
            ], 'profiler-migrations');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/profiler')
            ], 'profiler-assets');

            $this->publishes([
                __DIR__.'/../config/profiler.php' => config_path('profiler.php')
            ], 'profiler-config');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/profiler.php', 'profiler'
        );

        $this->registerStorageDriver();

        $this->commands([
            Console\ClearCommand::class,
            Console\InstallCommand::class,
            Console\PruneCommand::class,
            Console\PublishCommand::class
        ]);
    }

    /**
     * Register the package storage driver.
     *
     * @return void
     */
    protected function registerStorageDriver(): void
    {
        $driver = config('profiler.driver');

        if (method_exists($this, $method = 'register'.ucfirst($driver).'Driver')) {
            $this->$method();
        }
    }

    /**
     * Register the package database storage driver.
     *
     * @return void
     */
    protected function registerDatabaseDriver(): void
    {
        $this->app->singleton(
            EntriesRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->singleton(
            ClearableRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->singleton(
            PrunableRepository::class, DatabaseEntriesRepository::class
        );

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give(config('profiler.storage.database.connection'));

        $this->app->when(DatabaseEntriesRepository::class)
            ->needs('$chunkSize')
            ->give(config('profiler.storage.database.chunk'));
    }

    /**
     * Determine if we should register the migrations.
     *
     * @return bool
     */
    protected function shouldMigrate(): bool
    {
        return Profiler::$runsMigrations && config('profiler.driver') === 'database';
    }
}
