<?php

namespace MadeInUA\LaravelDripFlow;

use MadeInUA\LaravelDripFlow\Services\DripManager;
use Illuminate\Support\ServiceProvider;

class DripFlowServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dripflow.php', 'dripflow'
        );

        // Register DripManager as singleton
        $this->app->singleton(DripManager::class, function ($app) {
            return new DripManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/dripflow.php' => config_path('dripflow.php'),
            ], 'dripflow-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'dripflow-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
