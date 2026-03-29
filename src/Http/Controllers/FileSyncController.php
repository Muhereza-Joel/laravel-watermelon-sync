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

        $files = Media::where('updated_at', '>', $lastSyncDate)
            ->where('collection_name', 'images')
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
        $model = $modelClass::where((new $modelClass)->getSyncKeyName(), $request->associated_id)->firstOrFail();

        $media = $model->addMediaFromRequest('file')->toMediaCollection('images');

        return response()->json(['id' => $media->id, 'url' => $media->getFullUrl()], 201);
    }
}
