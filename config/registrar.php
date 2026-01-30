<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Registrar Configuration
    |--------------------------------------------------------------------------
    |
    | These are the default settings applied to all registrars unless
    | overridden in registrar-specific configuration.
    |
    */

    'defaults' => [
        'timeout' => env('REGISTRAR_TIMEOUT', 30),
        'enable_logging' => env('REGISTRAR_ENABLE_LOGGING', true),
        'rate_limit' => [
            'max_attempts' => env('REGISTRAR_RATE_LIMIT_ATTEMPTS', 60),
            'decay_minutes' => env('REGISTRAR_RATE_LIMIT_DECAY', 1),
        ],
        'cache_ttl' => env('REGISTRAR_CACHE_TTL', 300), // 5 minutes
        'retry' => [
            'enabled' => env('REGISTRAR_RETRY_ENABLED', true),
            'max_attempts' => env('REGISTRAR_RETRY_MAX_ATTEMPTS', 3),
            'delay' => env('REGISTRAR_RETRY_DELAY', 1000), // milliseconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registrar-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for specific registrars. Each registrar can override
    | the default settings and provide registrar-specific options.
    |
    */

    'registrars' => [

        'mock' => [
            'api_url' => env('MOCK_REGISTRAR_URL', 'https://api.mock-registrar.test'),
            'enable_logging' => true,
            'simulate_delays' => env('MOCK_SIMULATE_DELAYS', false),
            'default_delay_ms' => env('MOCK_DELAY_MS', 100),
            'failure_rate' => env('MOCK_FAILURE_RATE', 0), // 0-100 percentage
            'track_history' => env('MOCK_TRACK_HISTORY', true),
            'history_limit' => env('MOCK_HISTORY_LIMIT', 100), // max operations to keep
            'state_ttl' => env('MOCK_STATE_TTL', 3600), // seconds
            'available_tlds' => [
                // Prices in cents (e.g., 1200 = $12.00)
                'com' => ['register' => 1200, 'renew' => 1200, 'transfer' => 1200],
                'net' => ['register' => 1400, 'renew' => 1400, 'transfer' => 1400],
                'org' => ['register' => 1500, 'renew' => 1500, 'transfer' => 1500],
                'io' => ['register' => 3500, 'renew' => 3500, 'transfer' => 3500],
                'app' => ['register' => 1800, 'renew' => 1800, 'transfer' => 1800],
                'dev' => ['register' => 1500, 'renew' => 1500, 'transfer' => 1500],
                'co' => ['register' => 2000, 'renew' => 2000, 'transfer' => 2000],
                'me' => ['register' => 1800, 'renew' => 1800, 'transfer' => 1800],
            ],
            'unavailable_patterns' => [
                'taken.com',
                'unavailable',
                'registered',
                'reserved',
            ],
        ],

        'resellerclub' => [
            'api_url' => env('RESELLERCLUB_API_URL', 'https://httpapi.com/api'),
            'test_mode' => env('RESELLERCLUB_TEST_MODE', false),
            'timeout' => 45,
            'rate_limit' => [
                'max_attempts' => 120, // Higher limit for production registrar
                'decay_minutes' => 1,
            ],
            'default_nameservers' => [
                env('RESELLERCLUB_NS1', 'ns1.resellerclub.com'),
                env('RESELLERCLUB_NS2', 'ns2.resellerclub.com'),
            ],
            'cache_ttl' => 300, // Cache API responses for 5 minutes
        ],

        'logicboxes' => [
            'api_url' => env('LOGICBOXES_API_URL', 'https://httpapi.com/api'),
            'test_mode' => env('LOGICBOXES_TEST_MODE', false),
            'timeout' => 45,
            'rate_limit' => [
                'max_attempts' => 120,
                'decay_minutes' => 1,
            ],
        ],

        'enom' => [
            'api_url' => env('ENOM_API_URL', 'https://reseller.enom.com/interface.asp'),
            'test_mode' => env('ENOM_TEST_MODE', false),
            'timeout' => 60,
        ],

        'namecheap' => [
            'api_url' => env('NAMECHEAP_API_URL', 'https://api.namecheap.com/xml.response'),
            'test_mode' => env('NAMECHEAP_TEST_MODE', false),
            'timeout' => 45,
            'client_ip' => env('NAMECHEAP_CLIENT_IP', '127.0.0.1'),
        ],

        'godaddy' => [
            'api_url' => env('GODADDY_API_URL', 'https://api.godaddy.com/v1'),
            'test_mode' => env('GODADDY_TEST_MODE', false),
            'timeout' => 45,
        ],

        'btcl' => [
            'api_url' => env('BTCL_API_URL', 'https://141.lyre.us/rsdom'),
            'timeout' => 45,
            'rate_limit' => [
                'max_attempts' => 60, // Conservative limit for BTCL
                'decay_minutes' => 1,
            ],
            'default_nameservers' => [
                env('BTCL_NS1', 'ns1.btcl.com.bd'),
                env('BTCL_NS2', 'ns2.btcl.com.bd'),
            ],
            'cache_ttl' => 300, // Cache API responses for 5 minutes
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Support Matrix
    |--------------------------------------------------------------------------
    |
    | Define which features are supported by each registrar.
    |
    */

    'features' => [
        'mock' => [
            'dns_management' => true,
            'whois_privacy' => true,
            'domain_forwarding' => true,
            'email_forwarding' => true,
            'dnssec' => true,
            'auto_renew' => true,
        ],
        'resellerclub' => [
            'dns_management' => true,
            'whois_privacy' => true,
            'domain_forwarding' => true,
            'email_forwarding' => true,
            'dnssec' => false,
            'auto_renew' => true,
        ],
        'logicboxes' => [
            'dns_management' => true,
            'whois_privacy' => true,
            'domain_forwarding' => true,
            'email_forwarding' => true,
            'dnssec' => false,
            'auto_renew' => true,
        ],
        'btcl' => [
            'dns_management' => false, // BTCL does not support DNS record management
            'whois_privacy' => false,  // BTCL does not support WHOIS privacy
            'domain_forwarding' => false,
            'email_forwarding' => false,
            'dnssec' => false,
            'auto_renew' => false,
            'domain_transfer' => false, // BTCL does not support domain transfers
            'domain_lock' => false,     // BTCL does not support domain locking
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Caching
    |--------------------------------------------------------------------------
    |
    | Configure caching for different types of registrar responses.
    |
    */

    'cache' => [
        'domain_info' => 300, // 5 minutes
        'dns_records' => 60, // 1 minute
        'contacts' => 600, // 10 minutes
        'availability' => 30, // 30 seconds
        'tld_prices' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for error handling and reporting.
    |
    */

    'errors' => [
        'report_to_sentry' => env('REGISTRAR_REPORT_ERRORS', true),
        'notify_admin' => env('REGISTRAR_NOTIFY_ADMIN', false),
        'admin_email' => env('REGISTRAR_ADMIN_EMAIL', null),
    ],

];
