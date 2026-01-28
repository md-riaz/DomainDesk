<?php

namespace Tests\Feature\Services\Registrar;

use App\Services\Registrar\ResellerClubRegistrar;
use App\Exceptions\RegistrarException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResellerClubRegistrarTest extends TestCase
{
    use RefreshDatabase;

    protected ResellerClubRegistrar $registrar;
    protected array $config;
    protected array $credentials;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'name' => 'ResellerClub',
            'api_url' => 'https://test.httpapi.com/api',
            'test_mode' => true,
            'timeout' => 30,
            'enable_logging' => true,
            'default_nameservers' => ['ns1.resellerclub.com', 'ns2.resellerclub.com'],
            'rate_limit' => [
                'max_attempts' => 60,
                'decay_minutes' => 1,
            ],
        ];

        $this->credentials = [
            'auth_userid' => '123456',
            'api_key' => 'test-api-key-123',
        ];

        $this->registrar = new ResellerClubRegistrar($this->config, $this->credentials);
    }

    /** @test */
    public function it_can_get_registrar_name()
    {
        $this->assertEquals('ResellerClub', $this->registrar->getName());
    }

    /** @test */
    public function it_throws_exception_when_credentials_are_missing()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('ResellerClub credentials not configured');

        new ResellerClubRegistrar($this->config, ['auth_userid' => '']);
    }

    /** @test */
    public function it_can_check_domain_availability_when_available()
    {
        Http::fake([
            '*/domains/available.json*' => Http::response([
                'com' => [
                    'status' => 'available',
                    'classkey' => 'domcno',
                ],
            ], 200),
        ]);

        $available = $this->registrar->checkAvailability('example-test.com');

        $this->assertTrue($available);
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'domains/available.json')
                && $request['auth-userid'] === '123456'
                && $request['api-key'] === 'test-api-key-123'
                && $request['domain-name'] === 'example-test'
                && $request['tlds'] === ['com'];
        });
    }

    /** @test */
    public function it_can_check_domain_availability_when_taken()
    {
        Http::fake([
            '*/domains/available.json*' => Http::response([
                'com' => [
                    'status' => 'regthroughothers',
                    'classkey' => 'domcno',
                ],
            ], 200),
        ]);

        $available = $this->registrar->checkAvailability('taken.com');

        $this->assertFalse($available);
    }

    /** @test */
    public function it_caches_availability_checks()
    {
        Http::fake([
            '*/domains/available.json*' => Http::response([
                'com' => [
                    'status' => 'available',
                    'classkey' => 'domcno',
                ],
            ], 200),
        ]);

        // First call should hit the API
        $this->registrar->checkAvailability('example.com');

        // Second call should use cache (we can verify by checking HTTP was only called once)
        $this->registrar->checkAvailability('example.com');

        Http::assertSentCount(1); // Should only send one request due to caching
    }

    /** @test */
    public function it_can_register_a_domain()
    {
        Http::fake([
            '*/contacts/default.json*' => Http::response([
                'contactid' => '987654',
            ], 200),
            '*/domains/register.json*' => Http::response([
                'status' => 'Success',
                'entityid' => 123456789,
                'orderid' => 123456789,
                'description' => 'example.com',
            ], 200),
            '*/domains/details.json*' => Http::response([
                'currentstatus' => 'Active',
                'endtime' => now()->addYear()->timestamp,
                'autorenew' => true,
                'customerlocked' => true,
                'nameservers' => ['ns1.resellerclub.com', 'ns2.resellerclub.com'],
            ], 200),
        ]);

        $result = $this->registrar->register([
            'domain' => 'example.com',
            'years' => 1,
            'contacts' => [
                'registrant' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1.5555551234',
                    'address' => '123 Main St',
                    'city' => 'Anytown',
                    'state' => 'CA',
                    'zip' => '12345',
                    'country' => 'US',
                ],
            ],
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertEquals(123456789, $result['data']['order_id']);
        $this->assertEquals('active', $result['data']['status']);
        $this->assertArrayHasKey('expiry_date', $result['data']);
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'domains/register.json')
                && $request['domain-name'] === 'example'
                && $request['years'] === 1;
        });
    }

    /** @test */
    public function it_validates_required_fields_for_registration()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Missing required parameters');

        $this->registrar->register([
            'domain' => 'example.com',
            // Missing 'years' and 'contacts'
        ]);
    }

    /** @test */
    public function it_validates_years_parameter()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Years must be between 1 and 10');

        $this->registrar->register([
            'domain' => 'example.com',
            'years' => 15,
            'contacts' => ['registrant' => []],
        ]);
    }

    /** @test */
    public function it_can_renew_a_domain()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response('123456789', 200),
            '*/domains/renew.json*' => Http::response([
                'status' => 'Success',
                'actiontypedesc' => 'Renewal of example.com for 1 year',
            ], 200),
            '*/domains/details.json*' => Http::response([
                'currentstatus' => 'Active',
                'endtime' => now()->addYears(2)->timestamp,
            ], 200),
        ]);

        $result = $this->registrar->renew('example.com', 1);

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertEquals(1, $result['data']['years_renewed']);
        $this->assertArrayHasKey('new_expiry_date', $result['data']);
    }

    /** @test */
    public function it_can_transfer_a_domain()
    {
        Http::fake([
            '*/contacts/default.json*' => Http::response([
                'contactid' => '987654',
            ], 200),
            '*/domains/transfer.json*' => Http::response([
                'status' => 'Success',
                'entityid' => 123456789,
                'orderid' => 123456789,
            ], 200),
        ]);

        $result = $this->registrar->transfer('example.com', 'AUTH-CODE-123');

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertEquals('pending', $result['data']['status']);
        $this->assertArrayHasKey('transfer_id', $result['data']);
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'domains/transfer.json')
                && $request['auth-code'] === 'AUTH-CODE-123';
        });
    }

    /** @test */
    public function it_validates_auth_code_for_transfer()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Authorization code is required');

        $this->registrar->transfer('example.com', '');
    }

    /** @test */
    public function it_can_update_nameservers()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response('123456789', 200),
            '*/domains/modify-ns.json*' => Http::response([
                'status' => 'Success',
            ], 200),
        ]);

        $nameservers = ['ns1.newhost.com', 'ns2.newhost.com'];
        $result = $this->registrar->updateNameservers('example.com', $nameservers);

        $this->assertTrue($result['success']);
        $this->assertEquals($nameservers, $result['data']['nameservers']);
        
        Http::assertSent(function ($request) use ($nameservers) {
            return str_contains($request->url(), 'domains/modify-ns.json')
                && $request['order-id'] === '123456789'
                && $request['ns'] === $nameservers;
        });
    }

    /** @test */
    public function it_validates_nameserver_count()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Must provide between 2 and 13 nameservers');

        $this->registrar->updateNameservers('example.com', ['ns1.example.com']); // Only 1
    }

    /** @test */
    public function it_validates_nameserver_format()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Invalid nameserver format');

        $this->registrar->updateNameservers('example.com', [
            'ns1.example.com',
            'invalid nameserver!',
        ]);
    }

    /** @test */
    public function it_can_get_domain_contacts()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response('123456789', 200),
            '*/domains/details.json*' => Http::response([
                'registrantcontactid' => '111',
                'admincontactid' => '222',
                'techcontactid' => '333',
                'billingcontactid' => '444',
            ], 200),
            '*/contacts/details.json*' => Http::response([
                'name' => 'John Doe',
                'company' => 'Example Corp',
                'emailaddr' => 'john@example.com',
                'telnocc' => '+1',
                'telno' => '5555551234',
                'address1' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zip' => '12345',
                'country' => 'US',
            ], 200),
        ]);

        $result = $this->registrar->getContacts('example.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('registrant', $result['data']);
        $this->assertArrayHasKey('admin', $result['data']);
        $this->assertEquals('John Doe', $result['data']['registrant']['name']);
    }

    /** @test */
    public function it_can_update_domain_contacts()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response('123456789', 200),
            '*/contacts/default.json*' => Http::response([
                'contactid' => '987654',
            ], 200),
            '*/domains/modify-contact.json*' => Http::response([
                'status' => 'Success',
            ], 200),
        ]);

        $result = $this->registrar->updateContacts('example.com', [
            'registrant' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
    }

    /** @test */
    public function it_can_get_dns_records()
    {
        Http::fake([
            '*/dns/manage/search-records.json*' => Http::response([
                0 => [
                    'recid' => '1',
                    'type' => 'A',
                    'host' => '@',
                    'value' => '192.0.2.1',
                    'ttl' => 3600,
                ],
                1 => [
                    'recid' => '2',
                    'type' => 'MX',
                    'host' => '@',
                    'value' => 'mail.example.com',
                    'priority' => 10,
                    'ttl' => 3600,
                ],
            ], 200),
        ]);

        $result = $this->registrar->getDnsRecords('example.com');

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']['records']);
        $this->assertCount(2, $result['data']['records']);
        $this->assertEquals('A', $result['data']['records'][0]['type']);
        $this->assertEquals('MX', $result['data']['records'][1]['type']);
    }

    /** @test */
    public function it_can_update_dns_records()
    {
        Http::fake([
            '*/dns/manage/search-records.json*' => Http::response([], 200),
            '*/dns/manage/delete-record.json*' => Http::response(['status' => 'Success'], 200),
            '*/dns/manage/add-record.json*' => Http::response(['status' => 'Success'], 200),
        ]);

        $records = [
            ['type' => 'A', 'name' => '@', 'value' => '192.0.2.1', 'ttl' => 3600],
            ['type' => 'A', 'name' => 'www', 'value' => '192.0.2.1', 'ttl' => 3600],
            ['type' => 'MX', 'name' => '@', 'value' => 'mail.example.com', 'priority' => 10, 'ttl' => 3600],
        ];

        $result = $this->registrar->updateDnsRecords('example.com', $records);

        $this->assertTrue($result['success']);
        $this->assertEquals($records, $result['data']['records']);
    }

    /** @test */
    public function it_validates_dns_record_structure()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('DNS record must have type, name, and value');

        $this->registrar->updateDnsRecords('example.com', [
            ['type' => 'A'], // Missing name and value
        ]);
    }

    /** @test */
    public function it_validates_dns_record_types()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Invalid DNS record type');

        $this->registrar->updateDnsRecords('example.com', [
            ['type' => 'INVALID', 'name' => '@', 'value' => '192.0.2.1'],
        ]);
    }

    /** @test */
    public function it_validates_mx_record_priority()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('MX records must have a priority');

        $this->registrar->updateDnsRecords('example.com', [
            ['type' => 'MX', 'name' => '@', 'value' => 'mail.example.com'], // Missing priority
        ]);
    }

    /** @test */
    public function it_can_get_domain_info()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response('123456789', 200),
            '*/domains/details.json*' => Http::response([
                'currentstatus' => 'Active',
                'creationtime' => now()->subYear()->timestamp,
                'modificationtime' => now()->subMonth()->timestamp,
                'endtime' => now()->addYear()->timestamp,
                'autorenew' => 'true',
                'customerlocked' => 'true',
                'nameservers' => ['ns1.resellerclub.com', 'ns2.resellerclub.com'],
                'isprivacyprotected' => 'false',
            ], 200),
        ]);

        $result = $this->registrar->getInfo('example.com');

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertEquals('active', $result['data']['status']);
        $this->assertTrue($result['data']['auto_renew']);
        $this->assertTrue($result['data']['locked']);
        $this->assertFalse($result['data']['privacy_protected']);
        $this->assertIsArray($result['data']['nameservers']);
    }

    /** @test */
    public function it_can_lock_a_domain()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response('123456789', 200),
            '*/domains/enable-theft-protection.json*' => Http::response([
                'status' => 'Success',
            ], 200),
        ]);

        $result = $this->registrar->lock('example.com');

        $this->assertTrue($result);
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'enable-theft-protection.json')
                && $request['order-id'] === '123456789';
        });
    }

    /** @test */
    public function it_can_unlock_a_domain()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response('123456789', 200),
            '*/domains/disable-theft-protection.json*' => Http::response([
                'status' => 'Success',
            ], 200),
        ]);

        $result = $this->registrar->unlock('example.com');

        $this->assertTrue($result);
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'disable-theft-protection.json')
                && $request['order-id'] === '123456789';
        });
    }

    /** @test */
    public function it_can_test_connection()
    {
        Http::fake([
            '*/customers/details.json*' => Http::response([
                'customerid' => '123456',
                'username' => 'testuser',
                'name' => 'Test User',
            ], 200),
        ]);

        $result = $this->registrar->testConnection();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_on_failed_connection_test()
    {
        Http::fake([
            '*/customers/details.json*' => Http::response([
                'status' => 'error',
                'message' => 'Authentication failed',
            ], 401),
        ]);

        $result = $this->registrar->testConnection();

        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_authentication_errors()
    {
        Http::fake([
            '*/domains/available.json*' => Http::response([
                'status' => 'error',
                'message' => 'Authentication failed - Invalid API Key',
                'error' => 'AUTH_FAILED',
            ], 200),
        ]);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Authentication failed');

        $this->registrar->checkAvailability('example.com');
    }

    /** @test */
    public function it_handles_connection_failures()
    {
        Http::fake([
            '*/domains/available.json*' => Http::response('', 500),
        ]);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('HTTP 500');

        $this->registrar->checkAvailability('example.com');
    }

    /** @test */
    public function it_handles_domain_not_found_errors()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response([
                'status' => 'error',
                'message' => 'Domain not found',
            ], 200),
        ]);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Domain not found');

        $this->registrar->getInfo('nonexistent.com');
    }

    /** @test */
    public function it_handles_api_error_responses()
    {
        Http::fake([
            '*/domains/orderid.json*' => Http::response('123456789', 200),
            '*/domains/renew.json*' => Http::response([
                'status' => 'error',
                'message' => 'Insufficient credits',
                'error' => 'INSUFFICIENT_CREDITS',
            ], 200),
        ]);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Insufficient credits');

        $this->registrar->renew('example.com', 1);
    }

    /** @test */
    public function it_extracts_domain_name_correctly()
    {
        $reflection = new \ReflectionClass($this->registrar);
        $method = $reflection->getMethod('extractDomainName');
        $method->setAccessible(true);

        $this->assertEquals('example', $method->invoke($this->registrar, 'example.com'));
        $this->assertEquals('sub.example', $method->invoke($this->registrar, 'sub.example.com'));
    }

    /** @test */
    public function it_extracts_tld_correctly()
    {
        $reflection = new \ReflectionClass($this->registrar);
        $method = $reflection->getMethod('extractTld');
        $method->setAccessible(true);

        $this->assertEquals('com', $method->invoke($this->registrar, 'example.com'));
        $this->assertEquals('io', $method->invoke($this->registrar, 'example.io'));
    }

    /** @test */
    public function it_maps_order_status_correctly()
    {
        $reflection = new \ReflectionClass($this->registrar);
        $method = $reflection->getMethod('mapOrderStatus');
        $method->setAccessible(true);

        $this->assertEquals('active', $method->invoke($this->registrar, 'Active'));
        $this->assertEquals('suspended', $method->invoke($this->registrar, 'Suspended'));
        $this->assertEquals('pending', $method->invoke($this->registrar, 'Pending'));
        $this->assertEquals('unknown', $method->invoke($this->registrar, 'Unknown'));
    }

    /** @test */
    public function it_parses_dates_correctly()
    {
        $reflection = new \ReflectionClass($this->registrar);
        $method = $reflection->getMethod('parseDate');
        $method->setAccessible(true);

        $timestamp = now()->timestamp;
        $result = $method->invoke($this->registrar, $timestamp);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('T', $result); // ISO 8601 format

        // Test null handling
        $this->assertNull($method->invoke($this->registrar, null));
    }

    /** @test */
    public function it_validates_domain_format()
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Invalid domain name format');

        $this->registrar->checkAvailability('invalid domain!.com');
    }

    /** @test */
    public function it_uses_test_api_url_in_test_mode()
    {
        $testConfig = array_merge($this->config, [
            'test_mode' => true,
        ]);
        unset($testConfig['api_url']);

        $registrar = new ResellerClubRegistrar($testConfig, $this->credentials);

        $reflection = new \ReflectionClass($registrar);
        $property = $reflection->getProperty('apiUrl');
        $property->setAccessible(true);

        $this->assertEquals('https://test.httpapi.com/api', $property->getValue($registrar));
    }
}
