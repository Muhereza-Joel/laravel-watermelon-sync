<?php

namespace MuherezaJoel\LaravelWatermelonSync\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

trait Syncable
{
    /**
     * Determine the column used as the sync identifier.
     */
    public function getSyncKeyName(): string
    {
        return property_exists($this, 'syncKeyName') ? $this->syncKeyName : 'watermelon_id';
    }

    /**
     * Fields to convert to Milliseconds for JS.
     * Pulled from config with model property override.
     */
    public function getSyncTimestampFields(): array
    {
        return property_exists($this, 'syncTimestampFields')
            ? $this->syncTimestampFields
            : config('sync.timestamp_fields', ['created_at', 'updated_at', 'deleted_at']);
    }

    /**
     * Fields to format as H:i:s.
     */
    public function getSyncTimeOnlyFields(): array
    {
        return property_exists($this, 'syncTimeOnlyFields') ? $this->syncTimeOnlyFields : [];
    }

    /**
     * If true, bypasses automatic scoping.
     */
    public function isGlobalSyncModel(): bool
    {
        return property_exists($this, 'isGlobalSync') ? $this->isGlobalSync : false;
    }

    /**
     * Returns the whitelisted payload for the client.
     */
    public function getSyncPayload(): array
    {
        $whitelist = property_exists($this, 'syncWhitelist')
            ? $this->syncWhitelist
            : $this->getFillable();

        // Pulled from config
        $protected = config('sync.protected_fields', ['password', 'remember_token']);
        $fields = array_diff($whitelist, $protected);

        $data = $this->only($fields);

        $syncKey = $this->getSyncKeyName();
        $data['id'] = $this->{$syncKey};

        return $data;
    }

    /**
     * Apply tenant/user scoping to the sync query based on config.
     */
    public function applySyncScopes($query, $user)
    {
        if ($this->isGlobalSyncModel()) return $query;

        $tenantColumns = config('sync.scope_columns', []);

        foreach ($tenantColumns as $column) {
            if (Schema::hasColumn($this->getTable(), $column)) {
                $query->where($column, $user->{$column});
            }
        }
        return $query;
    }

    /**
     * Enrich incoming data with tenant identifiers before saving based on config.
     */
    public function prepareSyncData(array $data, $user): array
    {
        $tenantColumns = config('sync.scope_columns', []);

        foreach ($tenantColumns as $column) {
            if (Schema::hasColumn($this->getTable(), $column)) {
                $data[$column] = $user->{$column};
            }
        }
        return $data;
    }
}
