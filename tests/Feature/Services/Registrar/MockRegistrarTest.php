<?php

namespace Tests\Feature\Services\Registrar;

use App\Exceptions\RegistrarException;
use App\Models\Registrar;
use App\Services\Registrar\MockRegistrar;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MockRegistrarTest extends TestCase
{
    use RefreshDatabase;

    protected MockRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        Registrar::create([
            'name' => 'Mock Registrar',
            'slug' => 'mock',
            'api_class' => MockRegistrar::class,
            'credentials' => ['api_key' => 'test'],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->registrar = RegistrarFactory::make('mock');
    }

    /**
     * Test check availability returns true for available domain.
     */
    public function test_check_availability_returns_true_for_available_domain(): void
    {
        $result = $this->registrar->checkAvailability('example.com');

        $this->assertTrue($result);
    }

    /**
     * Test check availability returns false for taken domain.
     */
    public function test_check_availability_returns_false_for_taken_domain(): void
    {
        $result = $this->registrar->checkAvailability('already-taken.com');

        $this->assertFalse($result);
    }

    /**
     * Test domain registration returns success response.
     */
    public function test_register_returns_success_response(): void
    {
        $data = [
            'domain' => 'example.com',
            'years' => 1,
            'contacts' => [
                'registrant' => ['name' => 'John Doe'],
            ],
        ];

        $result = $this->registrar->register($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertArrayHasKey('order_id', $result['data']);
        $this->assertArrayHasKey('expiry_date', $result['data']);
    }

    /**
     * Test registration throws exception for missing required fields.
     */
    public function test_register_throws_exception_for_missing_fields(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Missing required parameters');

        $this->registrar->register(['domain' => 'example.com']);
    }

    /**
     * Test domain renewal returns success response.
     */
    public function test_renew_returns_success_response(): void
    {
        $result = $this->registrar->renew('example.com', 2);

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertEquals(2, $result['data']['years_renewed']);
        $this->assertArrayHasKey('new_expiry_date', $result['data']);
    }

    /**
     * Test domain transfer returns success response.
     */
    public function test_transfer_returns_success_response(): void
    {
        $result = $this->registrar->transfer('example.com', 'AUTH-CODE-123');

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertEquals('pending', $result['data']['status']);
        $this->assertArrayHasKey('transfer_id', $result['data']);
    }

    /**
     * Test update nameservers returns success response.
     */
    public function test_update_nameservers_returns_success_response(): void
    {
        $nameservers = ['ns1.example.com', 'ns2.example.com'];
        $result = $this->registrar->updateNameservers('example.com', $nameservers);

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertEquals($nameservers, $result['data']['nameservers']);
    }

    /**
     * Test get contacts returns success response.
     */
    public function test_get_contacts_returns_success_response(): void
    {
        $result = $this->registrar->getContacts('example.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('registrant', $result['data']);
        $this->assertArrayHasKey('admin', $result['data']);
        $this->assertArrayHasKey('tech', $result['data']);
        $this->assertArrayHasKey('billing', $result['data']);
    }

    /**
     * Test update contacts returns success response.
     */
    public function test_update_contacts_returns_success_response(): void
    {
        $contacts = [
            'registrant' => ['name' => 'John Doe', 'email' => 'john@example.com'],
        ];

        $result = $this->registrar->updateContacts('example.com', $contacts);

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
    }

    /**
     * Test get DNS records returns success response.
     */
    public function test_get_dns_records_returns_success_response(): void
    {
        $result = $this->registrar->getDnsRecords('example.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('records', $result['data']);
        $this->assertIsArray($result['data']['records']);
    }

    /**
     * Test update DNS records returns success response.
     */
    public function test_update_dns_records_returns_success_response(): void
    {
        $records = [
            ['type' => 'A', 'name' => '@', 'value' => '192.0.2.1'],
        ];

        $result = $this->registrar->updateDnsRecords('example.com', $records);

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
    }

    /**
     * Test get domain info returns success response.
     */
    public function test_get_info_returns_success_response(): void
    {
        $result = $this->registrar->getInfo('example.com');

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['data']['domain']);
        $this->assertArrayHasKey('status', $result['data']);
        $this->assertArrayHasKey('expiry_date', $result['data']);
        $this->assertArrayHasKey('locked', $result['data']);
    }

    /**
     * Test lock domain returns true.
     */
    public function test_lock_returns_true(): void
    {
        $result = $this->registrar->lock('example.com');

        $this->assertTrue($result);
    }

    /**
     * Test unlock domain returns true.
     */
    public function test_unlock_returns_true(): void
    {
        $result = $this->registrar->unlock('example.com');

        $this->assertTrue($result);
    }

    /**
     * Test get name returns correct registrar name.
     */
    public function test_get_name_returns_registrar_name(): void
    {
        $name = $this->registrar->getName();

        $this->assertEquals('Mock Registrar', $name);
    }

    /**
     * Test connection test returns true.
     */
    public function test_connection_returns_true(): void
    {
        $result = $this->registrar->testConnection();

        $this->assertTrue($result);
    }

    /**
     * Test validates domain format.
     */
    public function test_validates_domain_format(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Invalid domain name format');

        $this->registrar->checkAvailability('invalid domain!.com');
    }

    /**
     * Test validates empty domain.
     */
    public function test_validates_empty_domain(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('cannot be empty');

        $this->registrar->checkAvailability('');
    }

    /**
     * Test all responses have standard format.
     */
    public function test_responses_have_standard_format(): void
    {
        $result = $this->registrar->register([
            'domain' => 'example.com',
            'years' => 1,
            'contacts' => ['registrant' => []],
        ]);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('registrar', $result);
        $this->assertArrayHasKey('timestamp', $result);
    }

    /**
     * Test API calls are logged.
     */
    public function test_api_calls_are_logged(): void
    {
        Log::shouldReceive('info')
            ->twice()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Registrar API call') &&
                       isset($context['registrar']) &&
                       isset($context['method']);
            });

        $this->registrar->checkAvailability('example.com');
    }
}
