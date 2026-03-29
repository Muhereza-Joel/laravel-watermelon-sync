<?php

namespace MuherezaJoel\LaravelWatermelonSync\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncController extends Controller
{
    public function syncPull(Request $request)
    {
        $lastSync = $request->input('last_pulled_at');
        $lastSyncTime = $lastSync ? Carbon::createFromTimestampMs($lastSync) : null;
        $requestedTable = $request->input('table');

        $changes = [];
        $models = config('sync.models', []);

        if ($requestedTable && isset($models[$requestedTable])) {
            $models = [$requestedTable => $models[$requestedTable]];
        }

        foreach ($models as $tableName => $modelClass) {
            $instance = new $modelClass;
            $query = $this->baseQuery($instance, $request);

            if ($lastSyncTime) {
                // Incremental Pull
                $changes[$tableName] = [
                    'created' => [],
                    'updated' => $query->where('updated_at', '>', $lastSyncTime)->get()->map(fn($r) => $this->serialize($r)),
                    'deleted' => $this->getDeletedIds($instance, $request, $lastSyncTime),
                ];
            } else {
                // Initial Pull
                $this->applyWindow($query, $tableName);
                $changes[$tableName] = [
                    'created' => [],
                    'updated' => $query->get()->map(fn($r) => $this->serialize($r)),
                    'deleted' => [],
                ];
            }
        }

        return response()->json([
            'changes' => $changes,
            'timestamp' => now()->getTimestampMs(),
        ]);
    }

    public function syncPush(Request $request)
    {
        DB::transaction(function () use ($request) {
            foreach ($request->input('changes', []) as $table => $payload) {
                $modelClass = config("sync.models.$table");
                if (!$modelClass) continue;

                $instance = new $modelClass;
                $syncKey = $instance->getSyncKeyName();
                $upserts = [];

                foreach (array_merge($payload['created'] ?? [], $payload['updated'] ?? []) as $record) {
                    $data = $this->mapIncomingRecord($record, $instance);
                    $data[$syncKey] = $record['id'];

                    // CLEANED: Use trait method to inject tenant/user IDs
                    if (method_exists($instance, 'prepareSyncData')) {
                        $data = $instance->prepareSyncData($data, $request->user());
                    }

                    $upserts[] = $data;
                }

                if ($upserts) {
                    $modelClass::upsert($upserts, [$syncKey], array_keys($upserts[0]));
                }

                if (!empty($payload['deleted'])) {
                    $modelClass::whereIn($syncKey, $payload['deleted'])->delete();
                }
            }
        });

        return response()->json(['status' => 'ok']);
    }

    protected function baseQuery($instance, Request $request)
    {
        $query = method_exists($instance, 'withTrashed') ? $instance::withTrashed() : $instance::query();

        // CLEANED: Logic moved to model trait to handle user/org scoping dynamically
        if (method_exists($instance, 'applySyncScopes')) {
            return $instance->applySyncScopes($query, $request->user());
        }

        return $query;
    }

    protected function serialize($record): array
    {
        $data = $record->getSyncPayload();

        foreach ($record->getSyncTimestampFields() as $field) {
            if (isset($record->$field)) {
                $data[$field] = Carbon::parse($record->$field)->getTimestampMs();
            }
        }

        foreach ($record->getSyncTimeOnlyFields() as $field) {
            if (isset($record->$field)) {
                $data[$field] = Carbon::parse($record->$field)->format('H:i:s');
            }
        }

        return $data;
    }

    protected function mapIncomingRecord($record, $instance): array
    {
        $data = collect($record)->only($instance->getFillable())->toArray();

        foreach ($instance->getSyncTimestampFields() as $field) {
            if (isset($data[$field])) {
                $ts = (int) $data[$field];
                $data[$field] = Carbon::createFromTimestamp($ts > 9999999999 ? $ts / 1000 : $ts);
            }
        }
        return $data;
    }

    protected function applyWindow($query, $table)
    {
        $windows = config('sync.windows', []);
        if (isset($windows[$table])) {
            $query->where('created_at', '>=', now()->sub($windows[$table]));
        }
    }

    protected function getDeletedIds($instance, $request, $since)
    {
        if (!method_exists($instance, 'withTrashed')) return [];

        $query = $instance::onlyTrashed()->where('deleted_at', '>', $since);

        // Apply scopes to deletion queries as well
        if (method_exists($instance, 'applySyncScopes')) {
            $query = $instance->applySyncScopes($query, $request->user());
        }

        return $query->pluck($instance->getSyncKeyName())->toArray();
    }
}
