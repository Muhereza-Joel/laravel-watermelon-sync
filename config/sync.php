<?php

return [
    'models' => [
        // 'contacts' => \App\Models\Contact::class,
    ],

    'windows' => [
        // 'waste_collections' => '1 month',
    ],

    /**
     * Columns used for multi-tenancy/scoping.
     * The engine will automatically filter queries by these if they exist on the table.
     */
    'scope_columns' => [
        'user_id',
        'organisation_id',
    ],

    /**
     * Fields that should never be sent to the frontend during a sync pull.
     */
    'protected_fields' => [
        'password',
        'password_confirmation',
        'remember_token',
    ],

    /**
     * Fields that should be automatically converted to Milliseconds for JavaScript/WatermelonDB.
     */
    'timestamp_fields' => [
        'created_at',
        'updated_at',
        'deleted_at',
    ],
];
