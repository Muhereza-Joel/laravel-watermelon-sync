<?php

namespace MuherezaJoel\LaravelWatermelonSync\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Carbon\Carbon;

class FileSyncController extends Controller
{
    public function getSyncStatus(Request $request)
    {
        $lastSyncedAt = $request->input('last_synced_at', 0);
        $lastSyncDate = Carbon::createFromTimestampMs($lastSyncedAt);

        // We need to ensure users only see files attached to models they own
        $files = Media::where('updated_at', '>', $lastSyncDate)
            ->where('collection_name', 'images')
            ->whereHasMorph('model', '*', function ($query) use ($request) {
                // If the parent model uses the Syncable trait, apply its scopes
                if (method_exists($query->getModel(), 'applySyncScopes')) {
                    $query->getModel()->applySyncScopes($query, $request->user());
                }
            })
            ->get()
            ->map(fn($media) => [
                'id' => $media->id,
                'url' => $media->getFullUrl(),
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'model_id' => $media->model_id,
                'updated_at' => $media->updated_at->getTimestampMs(),
            ]);

        return response()->json(['filesToDownload' => $files]);
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'associated_entity' => 'required|string',
            'associated_id' => 'required|string',
        ]);

        $modelClass = config("sync.models." . $request->associated_entity);
        if (!$modelClass) {
            return response()->json(['error' => 'Invalid entity'], 400);
        }

        $instance = new $modelClass;

        // Use the trait's scoping logic to find the record. 
        // This prevents a user from uploading a file to an ID they don't own.
        $query = $modelClass::where($instance->getSyncKeyName(), $request->associated_id);

        if (method_exists($instance, 'applySyncScopes')) {
            $instance->applySyncScopes($query, $request->user());
        }

        $model = $query->firstOrFail();

        $media = $model->addMediaFromRequest('file')->toMediaCollection('images');

        return response()->json(['id' => $media->id, 'url' => $media->getFullUrl()], 201);
    }
}
