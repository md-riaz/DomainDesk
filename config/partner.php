<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Partner ID
    |--------------------------------------------------------------------------
    |
    | The default partner ID to use for local development or when no partner
    | domain can be resolved. Set this in your .env file as DEFAULT_PARTNER_ID
    |
    */
    'default_partner_id' => env('DEFAULT_PARTNER_ID'),

    /*
    |--------------------------------------------------------------------------
    | Use Default Fallback
    |--------------------------------------------------------------------------
    |
    | When true, if no partner is found for the current domain, the system
    | will fall back to the default partner. Set to false in production.
    |
    */
    'use_default_fallback' => env('PARTNER_USE_DEFAULT_FALLBACK', env('APP_ENV') === 'local'),

    /*
    |--------------------------------------------------------------------------
    | Allow Missing Partner
    |--------------------------------------------------------------------------
    |
    | When true, requests without a partner context will be allowed to proceed.
    | When false, a 404 error will be thrown. Useful for testing.
    |
    */
    'allow_missing_partner' => env('PARTNER_ALLOW_MISSING', false),
];
