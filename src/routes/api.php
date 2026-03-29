<?php

use Illuminate\Support\Facades\Route;
use MuherezaJoel\LaravelWatermelonSync\Http\Controllers\FileSyncController;
use MuherezaJoel\LaravelWatermelonSync\Http\Controllers\SyncController;

Route::middleware('auth:sanctum')->prefix('api/sync')->group(function () {
    Route::get('pull', [SyncController::class, 'syncPull']);
    Route::post('push', [SyncController::class, 'syncPush']);

    Route::get('files/status', [FileSyncController::class, 'getSyncStatus']);
    Route::post('files/upload', [FileSyncController::class, 'uploadFile']);
});
