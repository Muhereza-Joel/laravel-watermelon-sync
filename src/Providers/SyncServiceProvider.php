<?php

namespace MuherezaJoel\LaravelWatermelonSync\Providers;

use Illuminate\Support\ServiceProvider;

class SyncServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->publishes([__DIR__ . '/../../config/sync.php' => config_path('sync.php')], 'sync-config');
    }
}
