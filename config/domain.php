<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Domain Sync Interval
    |--------------------------------------------------------------------------
    |
    | Minimum number of hours between domain syncs to prevent excessive API calls.
    | Domains won't be synced if they were synced within this interval unless forced.
    |
    */
    'sync_interval_hours' => env('DOMAIN_SYNC_INTERVAL_HOURS', 6),

    /*
    |--------------------------------------------------------------------------
    | Sync Batch Size
    |--------------------------------------------------------------------------
    |
    | Maximum number of domains to sync in a single batch operation.
    |
    */
    'sync_batch_size' => env('DOMAIN_SYNC_BATCH_SIZE', 100),

    /*
    |--------------------------------------------------------------------------
    | Priority Sync Days
    |--------------------------------------------------------------------------
    |
    | Domains expiring within this number of days will be prioritized for syncing.
    |
    */
    'priority_sync_days' => env('DOMAIN_PRIORITY_SYNC_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Sync Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds for a single domain sync operation.
    |
    */
    'sync_timeout' => env('DOMAIN_SYNC_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of times to retry a failed sync and delay between retries (seconds).
    |
    */
    'sync_retries' => env('DOMAIN_SYNC_RETRIES', 3),
    'sync_retry_delay' => env('DOMAIN_SYNC_RETRY_DELAY', 300),

    /*
    |--------------------------------------------------------------------------
    | Price Change Alert Threshold
    |--------------------------------------------------------------------------
    |
    | Percentage change in TLD prices that triggers an alert/notification.
    |
    */
    'price_change_alert_threshold' => env('DOMAIN_PRICE_CHANGE_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Auto-Queue Sync
    |--------------------------------------------------------------------------
    |
    | Whether to automatically queue domain syncs for large batches.
    |
    */
    'auto_queue_threshold' => env('DOMAIN_AUTO_QUEUE_THRESHOLD', 50),
];
