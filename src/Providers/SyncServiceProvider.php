<?php

namespace MuherezaJoel\LaravelWatermelonSync\Providers;

use Illuminate\Support\ServiceProvider;

class SyncServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge config so it works even if not published
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/sync.php',
            'sync'
        );
    }

    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/sync.php' => config_path('sync.php'),
        ], 'watermelon-sync-config');

        // Publish provider (optional)
        $this->publishes([
            __FILE__ => app_path('Providers/SyncServiceProvider.php'),
        ], 'watermelon-sync-provider');
    }
}
