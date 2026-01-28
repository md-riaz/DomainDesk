<?php

namespace Database\Seeders;

use App\Models\Registrar;
use App\Services\Registrar\MockRegistrar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MockRegistrarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Mock Registrar
        $mockRegistrar = Registrar::updateOrCreate(
            ['slug' => 'mock'],
            [
                'name' => 'Mock Registrar',
                'api_class' => MockRegistrar::class,
                'credentials' => [
                    'api_key' => 'mock_api_key_12345',
                    'api_secret' => 'mock_api_secret_67890',
                ],
                'is_active' => true,
                'is_default' => true,
            ]
        );

        $this->command->info('Mock Registrar created/updated');

        // Seed some mock domain data in cache for testing
        $this->seedMockDomains();

        $this->command->info('Mock domain data seeded to cache');
    }

    /**
     * Seed mock domain data to cache for testing.
     */
    protected function seedMockDomains(): void
    {
        $prefix = 'mock_registrar:';
        $ttl = 3600;

        // Create some pre-registered domains for testing
        $domains = [
            'example.com' => [
                'domain' => 'example.com',
                'status' => 'active',
                'order_id' => 'MOCK-' . strtoupper(uniqid()),
                'registered_at' => now()->subYears(2)->toIso8601String(),
                'expiry_date' => now()->addYear()->toIso8601String(),
                'auto_renew' => true,
                'locked' => true,
                'nameservers' => ['ns1.example.com', 'ns2.example.com'],
                'contacts' => [
                    'registrant' => [
                        'name' => 'John Doe',
                        'company' => 'Example Corp',
                        'email' => 'registrant@example.com',
                        'phone' => '+1.5555551234',
                        'address' => '123 Main St',
                        'city' => 'Anytown',
                        'state' => 'CA',
                        'zip' => '12345',
                        'country' => 'US',
                    ],
                    'admin' => [
                        'name' => 'Jane Admin',
                        'email' => 'admin@example.com',
                        'phone' => '+1.5555555678',
                    ],
                ],
                'dns_records' => [
                    ['type' => 'A', 'name' => '@', 'value' => '192.0.2.1', 'ttl' => 3600],
                    ['type' => 'A', 'name' => 'www', 'value' => '192.0.2.1', 'ttl' => 3600],
                    ['type' => 'MX', 'name' => '@', 'value' => 'mail.example.com', 'priority' => 10, 'ttl' => 3600],
                ],
            ],
            'test-domain.com' => [
                'domain' => 'test-domain.com',
                'status' => 'active',
                'order_id' => 'MOCK-' . strtoupper(uniqid()),
                'registered_at' => now()->subMonths(6)->toIso8601String(),
                'expiry_date' => now()->addMonths(6)->toIso8601String(),
                'auto_renew' => false,
                'locked' => false,
                'nameservers' => ['ns1.hosting.com', 'ns2.hosting.com'],
                'contacts' => [
                    'registrant' => [
                        'name' => 'Test User',
                        'email' => 'test@test-domain.com',
                        'phone' => '+1.5559991234',
                        'address' => '456 Test Ave',
                        'city' => 'Testville',
                        'state' => 'NY',
                        'zip' => '54321',
                        'country' => 'US',
                    ],
                ],
            ],
            'demo-site.io' => [
                'domain' => 'demo-site.io',
                'status' => 'active',
                'order_id' => 'MOCK-' . strtoupper(uniqid()),
                'registered_at' => now()->subMonths(3)->toIso8601String(),
                'expiry_date' => now()->addMonths(9)->toIso8601String(),
                'auto_renew' => true,
                'locked' => true,
                'nameservers' => ['ns1.cloudflare.com', 'ns2.cloudflare.com'],
                'contacts' => [
                    'registrant' => [
                        'name' => 'Demo Company',
                        'company' => 'Demo Inc',
                        'email' => 'contact@demo-site.io',
                        'phone' => '+1.5558881234',
                        'address' => '789 Demo Blvd',
                        'city' => 'San Francisco',
                        'state' => 'CA',
                        'zip' => '94102',
                        'country' => 'US',
                    ],
                ],
                'dns_records' => [
                    ['type' => 'A', 'name' => '@', 'value' => '104.21.0.1', 'ttl' => 300],
                    ['type' => 'AAAA', 'name' => '@', 'value' => '2606:4700::1', 'ttl' => 300],
                    ['type' => 'CNAME', 'name' => 'www', 'value' => 'demo-site.io', 'ttl' => 300],
                ],
            ],
            'expiring-soon.com' => [
                'domain' => 'expiring-soon.com',
                'status' => 'active',
                'order_id' => 'MOCK-' . strtoupper(uniqid()),
                'registered_at' => now()->subYears(1)->toIso8601String(),
                'expiry_date' => now()->addDays(15)->toIso8601String(),
                'auto_renew' => false,
                'locked' => true,
                'nameservers' => ['ns1.example.net', 'ns2.example.net'],
                'contacts' => [
                    'registrant' => [
                        'name' => 'Expiring User',
                        'email' => 'owner@expiring-soon.com',
                        'phone' => '+1.5557771234',
                    ],
                ],
            ],
            'brand-new.app' => [
                'domain' => 'brand-new.app',
                'status' => 'active',
                'order_id' => 'MOCK-' . strtoupper(uniqid()),
                'registered_at' => now()->subDays(5)->toIso8601String(),
                'expiry_date' => now()->addYears(1)->subDays(5)->toIso8601String(),
                'auto_renew' => true,
                'locked' => true,
                'nameservers' => ['ns1.brand-new.app', 'ns2.brand-new.app'],
                'contacts' => [
                    'registrant' => [
                        'name' => 'Startup Founder',
                        'company' => 'Brand New Inc',
                        'email' => 'founder@brand-new.app',
                        'phone' => '+1.5556661234',
                        'address' => '101 Startup Way',
                        'city' => 'Austin',
                        'state' => 'TX',
                        'zip' => '73301',
                        'country' => 'US',
                    ],
                ],
            ],
        ];

        foreach ($domains as $domain => $data) {
            Cache::put($prefix . 'domain:' . $domain, $data, $ttl);
        }

        // Seed some transfer states
        $transfers = [
            'incoming-transfer.com' => [
                'domain' => 'incoming-transfer.com',
                'transfer_id' => 'MOCK-TRANSFER-' . strtoupper(uniqid()),
                'status' => 'pending',
                'initiated_at' => now()->subDays(2)->toIso8601String(),
                'estimated_completion' => now()->addDays(3)->toIso8601String(),
                'auth_code' => 'AUTH-CODE-12345',
            ],
        ];

        foreach ($transfers as $domain => $data) {
            Cache::put($prefix . 'transfer:' . $domain, $data, $ttl);
        }

        // Seed initial operation history
        $history = [
            [
                'operation' => 'register',
                'domain' => 'example.com',
                'data' => ['years' => 1, 'status' => 'active'],
                'timestamp' => now()->subYears(2)->toIso8601String(),
            ],
            [
                'operation' => 'renew',
                'domain' => 'example.com',
                'data' => ['years' => 1],
                'timestamp' => now()->subYear()->toIso8601String(),
            ],
            [
                'operation' => 'updateNameservers',
                'domain' => 'test-domain.com',
                'data' => ['nameservers' => ['ns1.hosting.com', 'ns2.hosting.com']],
                'timestamp' => now()->subMonths(3)->toIso8601String(),
            ],
            [
                'operation' => 'lock',
                'domain' => 'demo-site.io',
                'data' => ['locked' => true],
                'timestamp' => now()->subMonths(2)->toIso8601String(),
            ],
        ];

        Cache::put($prefix . 'history', $history, $ttl);
    }
}
