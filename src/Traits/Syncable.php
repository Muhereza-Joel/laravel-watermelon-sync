<?php

namespace MuherezaJoel\LaravelWatermelonSync\Traits;

use Illuminate\Support\Carbon;

trait Syncable
{
    /**
     * Determine the column used as the sync identifier.
     * Defaults to 'watermelon_id'.
     */
    public function getSyncKeyName(): string
    {
        return property_exists($this, 'syncKeyName') ? $this->syncKeyName : 'watermelon_id';
    }

    /**
     * Fields to convert to Milliseconds for JS.
     */
    public function getSyncTimestampFields(): array
    {
        return property_exists($this, 'syncTimestampFields')
            ? $this->syncTimestampFields
            : ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * Fields to format as H:i:s.
     */
    public function getSyncTimeOnlyFields(): array
    {
        return property_exists($this, 'syncTimeOnlyFields') ? $this->syncTimeOnlyFields : [];
    }

    /**
     * If true, bypasses user_id scoping.
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

        $protected = ['password', 'password_confirmation', 'remember_token'];
        $fields = array_diff($whitelist, $protected);

        $data = $this->only($fields);

        $syncKey = $this->getSyncKeyName();
        $data['id'] = $this->{$syncKey};

        return $data;
    }
}
