<?php

namespace Tests\Feature\Services\Registrar;

use App\Exceptions\RegistrarException;
use App\Models\Registrar;
use App\Services\Registrar\MockRegistrar;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        
        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test check availability returns true for available domain.
     */
    public function test_check_availability_returns_true_for_available_domain(): void
    {
        $result = $this->registrar->checkAvailability('test-available-domain.com');

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
     * Test check availability returns false for unavailable pattern.
     */
    public function test_check_availability_respects_unavailable_patterns(): void
    {
        $this->assertFalse($this->registrar->checkAvailability('test-unavailable.com'));
        $this->assertFalse($this->registrar->checkAvailability('registered-domain.com'));
    }

    /**
     * Test check availability returns false for already registered domain.
     */
    public function test_check_availability_returns_false_for_registered_domain(): void
    {
        // Register a domain first
        $this->registrar->register([
            'domain' => 'mytest.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'John Doe', 'email' => 'john@test.com']],
        ]);

        // Check availability should return false
        $result = $this->registrar->checkAvailability('mytest.com');
        $this->assertFalse($result);
    }

    /**
     * Test check availability throws exception for unsupported TLD.
     */
    public function test_check_availability_throws_exception_for_unsupported_tld(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('not supported');

        $this->registrar->checkAvailability('example.xyz');
    }

    /**
     * Test domain registration returns success response.
     */
    public function test_register_returns_success_response(): void
    {
        $data = [
            'domain' => 'test-example.com',
            'years' => 1,
            'contacts' => [
                'registrant' => ['name' => 'John Doe', 'email' => 'john@example.com'],
            ],
        ];

        $result = $this->registrar->register($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('test-example.com', $result['data']['domain']);
        $this->assertArrayHasKey('order_id', $result['data']);
        $this->assertArrayHasKey('expiry_date', $result['data']);
        $this->assertArrayHasKey('nameservers', $result['data']);
    }

    /**
     * Test registration stores domain state.
     */
    public function test_register_stores_domain_state(): void
    {
        $data = [
            'domain' => 'stateful-test.com',
            'years' => 2,
            'contacts' => [
                'registrant' => ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
            ],
            'auto_renew' => true,
        ];

        $this->registrar->register($data);

        // Get domain info to verify state
        $info = $this->registrar->getInfo('stateful-test.com');
        $this->assertTrue($info['success']);
        $this->assertEquals('stateful-test.com', $info['data']['domain']);
        $this->assertEquals('active', $info['data']['status']);
        $this->assertTrue($info['data']['auto_renew']);
    }

    /**
     * Test registration with custom nameservers.
     */
    public function test_register_with_custom_nameservers(): void
    {
        $data = [
            'domain' => 'custom-ns.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test User', 'email' => 'test@example.com']],
            'nameservers' => ['ns1.custom.com', 'ns2.custom.com'],
        ];

        $result = $this->registrar->register($data);

        $this->assertTrue($result['success']);
        $this->assertEquals(['ns1.custom.com', 'ns2.custom.com'], $result['data']['nameservers']);
    }

    /**
     * Test registration validates years parameter.
     */
    public function test_register_validates_years(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Years must be between 1 and 10');

        $this->registrar->register([
            'domain' => 'test.com',
            'years' => 15,
            'contacts' => ['registrant' => ['name' => 'Test']],
        ]);
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
     * Test registration throws exception for unavailable domain.
     */
    public function test_register_throws_exception_for_unavailable_domain(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('not available');

        $this->registrar->register([
            'domain' => 'taken.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test']],
        ]);
    }

    /**
     * Test registration validates contacts.
     */
    public function test_register_validates_contacts(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Missing required contact type');

        $this->registrar->register([
            'domain' => 'test.com',
            'years' => 1,
            'contacts' => ['admin' => ['name' => 'Admin']],
        ]);
    }

    /**
     * Test registration validates nameservers count.
     */
    public function test_register_validates_nameservers_count(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Must provide between 2 and 4 nameservers');

        $this->registrar->register([
            'domain' => 'test-ns-single.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
            'nameservers' => ['ns1.test.com'],
        ]);
    }

    /**
     * Test domain renewal returns success response.
     */
    public function test_renew_returns_success_response(): void
    {
        // Register a domain first
        $this->registrar->register([
            'domain' => 'renew-test.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $result = $this->registrar->renew('renew-test.com', 2);

        $this->assertTrue($result['success']);
        $this->assertEquals('renew-test.com', $result['data']['domain']);
        $this->assertEquals(2, $result['data']['years_renewed']);
        $this->assertArrayHasKey('new_expiry_date', $result['data']);
        $this->assertArrayHasKey('previous_expiry_date', $result['data']);
    }

    /**
     * Test renewal updates expiry date correctly.
     */
    public function test_renew_updates_expiry_date(): void
    {
        // Register domain
        $this->registrar->register([
            'domain' => 'expiry-test.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        // Get original expiry
        $info = $this->registrar->getInfo('expiry-test.com');
        $originalExpiry = $info['data']['expiry_date'];

        // Renew for 2 years
        $this->registrar->renew('expiry-test.com', 2);

        // Check new expiry
        $newInfo = $this->registrar->getInfo('expiry-test.com');
        $this->assertNotEquals($originalExpiry, $newInfo['data']['expiry_date']);
    }

    /**
     * Test renewal throws exception for non-existent domain.
     */
    public function test_renew_throws_exception_for_nonexistent_domain(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Domain not found');

        $this->registrar->renew('nonexistent-domain.com', 1);
    }

    /**
     * Test domain transfer returns success response.
     */
    public function test_transfer_returns_success_response(): void
    {
        $result = $this->registrar->transfer('transfer-test.com', 'AUTH-CODE-123');

        $this->assertTrue($result['success']);
        $this->assertEquals('transfer-test.com', $result['data']['domain']);
        $this->assertEquals('pending', $result['data']['status']);
        $this->assertArrayHasKey('transfer_id', $result['data']);
        $this->assertArrayHasKey('estimated_completion', $result['data']);
    }

    /**
     * Test transfer requires auth code.
     */
    public function test_transfer_requires_auth_code(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Authorization code is required');

        $this->registrar->transfer('test.com', '');
    }

    /**
     * Test transfer stores transfer state.
     */
    public function test_transfer_stores_transfer_state(): void
    {
        $result = $this->registrar->transfer('transfer-state.com', 'AUTH123');
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['transfer_id']);
    }

    /**
     * Test update nameservers returns success response.
     */
    public function test_update_nameservers_returns_success_response(): void
    {
        // Register domain first
        $this->registrar->register([
            'domain' => 'ns-test.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $nameservers = ['ns1.new.com', 'ns2.new.com'];
        $result = $this->registrar->updateNameservers('ns-test.com', $nameservers);

        $this->assertTrue($result['success']);
        $this->assertEquals('ns-test.com', $result['data']['domain']);
        $this->assertEquals($nameservers, $result['data']['nameservers']);
    }

    /**
     * Test update nameservers validates count.
     */
    public function test_update_nameservers_validates_count(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Must provide between 2 and 4 nameservers');

        $this->registrar->updateNameservers('test.com', ['ns1.test.com']);
    }

    /**
     * Test update nameservers validates format.
     */
    public function test_update_nameservers_validates_format(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Invalid nameserver format');

        $this->registrar->updateNameservers('test.com', ['ns1.test.com', 'invalid ns!']);
    }

    /**
     * Test update nameservers throws exception for nonexistent domain.
     */
    public function test_update_nameservers_throws_exception_for_nonexistent_domain(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Domain not found');

        $this->registrar->updateNameservers('nonexistent.com', ['ns1.test.com', 'ns2.test.com']);
    }

    /**
     * Test get contacts returns success response.
     */
    public function test_get_contacts_returns_success_response(): void
    {
        // Register domain with specific contacts
        $contacts = [
            'registrant' => ['name' => 'John Registrant', 'email' => 'registrant@test.com'],
            'admin' => ['name' => 'Jane Admin', 'email' => 'admin@test.com'],
        ];

        $this->registrar->register([
            'domain' => 'contacts-test.com',
            'years' => 1,
            'contacts' => $contacts,
        ]);

        $result = $this->registrar->getContacts('contacts-test.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('registrant', $result['data']);
        $this->assertEquals('John Registrant', $result['data']['registrant']['name']);
    }

    /**
     * Test update contacts returns success response.
     */
    public function test_update_contacts_returns_success_response(): void
    {
        // Register domain first
        $this->registrar->register([
            'domain' => 'update-contacts.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Original', 'email' => 'orig@test.com']],
        ]);

        $newContacts = [
            'registrant' => ['name' => 'Updated Name', 'email' => 'updated@example.com'],
        ];

        $result = $this->registrar->updateContacts('update-contacts.com', $newContacts);

        $this->assertTrue($result['success']);
        $this->assertEquals('update-contacts.com', $result['data']['domain']);
    }

    /**
     * Test update contacts validates contact types.
     */
    public function test_update_contacts_validates_contact_types(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Invalid contact type');

        $this->registrar->updateContacts('test.com', [
            'registrant' => ['name' => 'Test'],
            'invalid_type' => ['name' => 'Test'],
        ]);
    }

    /**
     * Test update contacts throws exception for nonexistent domain.
     */
    public function test_update_contacts_throws_exception_for_nonexistent_domain(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Domain not found');

        $this->registrar->updateContacts('nonexistent.com', [
            'registrant' => ['name' => 'Test'],
        ]);
    }

    /**
     * Test get DNS records returns success response.
     */
    public function test_get_dns_records_returns_success_response(): void
    {
        $result = $this->registrar->getDnsRecords('dns-test.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('records', $result['data']);
        $this->assertIsArray($result['data']['records']);
        $this->assertNotEmpty($result['data']['records']);
    }

    /**
     * Test update DNS records returns success response.
     */
    public function test_update_dns_records_returns_success_response(): void
    {
        // Register domain first
        $this->registrar->register([
            'domain' => 'dns-update.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $records = [
            ['type' => 'A', 'name' => '@', 'value' => '192.0.2.100', 'ttl' => 3600],
            ['type' => 'CNAME', 'name' => 'www', 'value' => 'dns-update.com', 'ttl' => 3600],
        ];

        $result = $this->registrar->updateDnsRecords('dns-update.com', $records);

        $this->assertTrue($result['success']);
        $this->assertEquals('dns-update.com', $result['data']['domain']);
    }

    /**
     * Test update DNS records validates record structure.
     */
    public function test_update_dns_records_validates_structure(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('must have type, name, and value');

        $this->registrar->updateDnsRecords('test.com', [
            ['type' => 'A', 'name' => '@'], // missing value
        ]);
    }

    /**
     * Test update DNS records validates MX priority.
     */
    public function test_update_dns_records_validates_mx_priority(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('MX records must have a priority');

        $this->registrar->updateDnsRecords('test.com', [
            ['type' => 'MX', 'name' => '@', 'value' => 'mail.test.com', 'ttl' => 3600],
        ]);
    }

    /**
     * Test update DNS records validates record type.
     */
    public function test_update_dns_records_validates_type(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Invalid DNS record type');

        $this->registrar->updateDnsRecords('test.com', [
            ['type' => 'INVALID', 'name' => '@', 'value' => 'test', 'ttl' => 3600],
        ]);
    }

    /**
     * Test get domain info returns success response.
     */
    public function test_get_info_returns_success_response(): void
    {
        // Register domain first
        $this->registrar->register([
            'domain' => 'info-test.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $result = $this->registrar->getInfo('info-test.com');

        $this->assertTrue($result['success']);
        $this->assertEquals('info-test.com', $result['data']['domain']);
        $this->assertArrayHasKey('status', $result['data']);
        $this->assertArrayHasKey('expiry_date', $result['data']);
        $this->assertArrayHasKey('locked', $result['data']);
    }

    /**
     * Test lock domain returns true.
     */
    public function test_lock_returns_true(): void
    {
        // Register domain first
        $this->registrar->register([
            'domain' => 'lock-test.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $result = $this->registrar->lock('lock-test.com');

        $this->assertTrue($result);
    }

    /**
     * Test lock updates domain state.
     */
    public function test_lock_updates_domain_state(): void
    {
        // Register and lock
        $this->registrar->register([
            'domain' => 'lock-state.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);
        
        $this->registrar->lock('lock-state.com');

        // Verify state
        $info = $this->registrar->getInfo('lock-state.com');
        $this->assertTrue($info['data']['locked']);
    }

    /**
     * Test unlock domain returns true.
     */
    public function test_unlock_returns_true(): void
    {
        // Register domain first
        $this->registrar->register([
            'domain' => 'unlock-test.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $result = $this->registrar->unlock('unlock-test.com');

        $this->assertTrue($result);
    }

    /**
     * Test unlock updates domain state.
     */
    public function test_unlock_updates_domain_state(): void
    {
        // Register and unlock
        $this->registrar->register([
            'domain' => 'unlock-state.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);
        
        $this->registrar->unlock('unlock-state.com');

        // Verify state
        $info = $this->registrar->getInfo('unlock-state.com');
        $this->assertFalse($info['data']['locked']);
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
            'domain' => 'format-test-domain.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
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

        $this->registrar->checkAvailability('logging-test-unique.com');
    }

    /**
     * Test operation history tracking.
     */
    public function test_tracks_operation_history(): void
    {
        $this->registrar->register([
            'domain' => 'history-test.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $history = $this->registrar->getOperationHistory();

        $this->assertNotEmpty($history);
        $this->assertArrayHasKey('operation', $history[0]);
        $this->assertArrayHasKey('domain', $history[0]);
        $this->assertEquals('register', $history[0]['operation']);
    }

    /**
     * Test can filter history by domain.
     */
    public function test_can_filter_history_by_domain(): void
    {
        $this->registrar->register([
            'domain' => 'history1.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $this->registrar->register([
            'domain' => 'history2.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        $history = $this->registrar->getOperationHistory('history1.com');

        $this->assertCount(1, $history);
        $this->assertEquals('history1.com', $history[0]['domain']);
    }

    /**
     * Test state persists across calls.
     */
    public function test_state_persists_across_calls(): void
    {
        // Register
        $this->registrar->register([
            'domain' => 'persist-test.com',
            'years' => 1,
            'contacts' => ['registrant' => ['name' => 'Test', 'email' => 'test@test.com']],
        ]);

        // Update nameservers
        $this->registrar->updateNameservers('persist-test.com', ['ns1.new.com', 'ns2.new.com']);

        // Check state is updated
        $info = $this->registrar->getInfo('persist-test.com');
        $this->assertEquals(['ns1.new.com', 'ns2.new.com'], $info['data']['nameservers']);
    }

    /**
     * Test configurable failure simulation.
     */
    public function test_simulates_failures_based_on_rate(): void
    {
        // Create registrar with 100% failure rate
        Registrar::create([
            'name' => 'Failing Mock',
            'slug' => 'failing-mock',
            'api_class' => MockRegistrar::class,
            'credentials' => ['api_key' => 'test'],
            'is_active' => true,
        ]);

        config(['registrar.registrars.failing-mock.failure_rate' => 100]);
        
        $failingRegistrar = RegistrarFactory::make('failing-mock');

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('Simulated failure');

        $failingRegistrar->checkAvailability('test.com');
    }
}
