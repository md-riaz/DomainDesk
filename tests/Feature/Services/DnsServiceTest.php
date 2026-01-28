<?php

namespace Tests\Feature\Services;

use App\Enums\DnsRecordType;
use App\Models\Domain;
use App\Models\DomainDnsRecord;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\User;
use App\Services\DnsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DnsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DnsService $service;
    protected Domain $domain;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(DnsService::class);
        
        // Create test data
        $partner = Partner::factory()->create();
        $registrar = Registrar::factory()->create(['slug' => 'mock']);
        
        $tld = Tld::factory()->create([
            'registrar_id' => $registrar->id,
            'extension' => '.com',
            'supports_dns' => true,
        ]);
        
        $this->user = User::factory()->client()->create(['partner_id' => $partner->id]);
        
        $this->domain = Domain::factory()->active()->create([
            'name' => 'example.com',
            'partner_id' => $partner->id,
            'registrar_id' => $registrar->id,
            'client_id' => $this->user->id,
        ]);

        // Initialize domain in MockRegistrar cache
        \Illuminate\Support\Facades\Cache::put(
            'mock_domain:' . $this->domain->name,
            [
                'name' => $this->domain->name,
                'status' => 'active',
                'nameservers' => [],
                'dns_records' => [],
                'registered_at' => now()->toIso8601String(),
                'expires_at' => now()->addYear()->toIso8601String(),
            ],
            3600
        );
    }

    public function test_add_a_record_successfully()
    {
        $data = [
            'type' => 'A',
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ];

        $result = $this->service->addDnsRecord($this->domain, $data, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['record']);
        
        $this->assertDatabaseHas('domain_dns_records', [
            'domain_id' => $this->domain->id,
            'type' => 'A',
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);
    }

    public function test_add_aaaa_record_with_ipv6()
    {
        $data = [
            'type' => 'AAAA',
            'name' => '@',
            'value' => '2001:0db8:85a3::8a2e:0370:7334',
            'ttl' => 3600,
        ];

        $result = $this->service->addDnsRecord($this->domain, $data, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('domain_dns_records', [
            'type' => 'AAAA',
            'value' => '2001:0db8:85a3::8a2e:0370:7334',
        ]);
    }

    public function test_add_cname_record()
    {
        $data = [
            'type' => 'CNAME',
            'name' => 'www',
            'value' => 'example.com',
            'ttl' => 3600,
        ];

        $result = $this->service->addDnsRecord($this->domain, $data, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('domain_dns_records', [
            'type' => 'CNAME',
            'name' => 'www',
            'value' => 'example.com',
        ]);
    }

    public function test_add_mx_record_with_priority()
    {
        $data = [
            'type' => 'MX',
            'name' => '@',
            'value' => 'mail.example.com',
            'ttl' => 3600,
            'priority' => 10,
        ];

        $result = $this->service->addDnsRecord($this->domain, $data, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('domain_dns_records', [
            'type' => 'MX',
            'value' => 'mail.example.com',
            'priority' => 10,
        ]);
    }

    public function test_add_txt_record()
    {
        $data = [
            'type' => 'TXT',
            'name' => '@',
            'value' => 'v=spf1 include:_spf.example.com ~all',
            'ttl' => 3600,
        ];

        $result = $this->service->addDnsRecord($this->domain, $data, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('domain_dns_records', [
            'type' => 'TXT',
            'value' => 'v=spf1 include:_spf.example.com ~all',
        ]);
    }

    public function test_add_ns_record()
    {
        $data = [
            'type' => 'NS',
            'name' => 'subdomain',
            'value' => 'ns1.example.com',
            'ttl' => 3600,
        ];

        $result = $this->service->addDnsRecord($this->domain, $data, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('domain_dns_records', [
            'type' => 'NS',
            'value' => 'ns1.example.com',
        ]);
    }

    public function test_add_srv_record_with_priority()
    {
        $data = [
            'type' => 'SRV',
            'name' => '_sip._tcp',
            'value' => '10 5060 sipserver.example.com',
            'ttl' => 3600,
            'priority' => 10,
        ];

        $result = $this->service->addDnsRecord($this->domain, $data, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('domain_dns_records', [
            'type' => 'SRV',
            'priority' => 10,
        ]);
    }

    public function test_validate_a_record_rejects_invalid_ipv4()
    {
        $this->expectException(ValidationException::class);

        $data = [
            'type' => 'A',
            'name' => '@',
            'value' => '999.999.999.999',
            'ttl' => 3600,
        ];

        $this->service->addDnsRecord($this->domain, $data, $this->user->id);
    }

    public function test_validate_aaaa_record_rejects_invalid_ipv6()
    {
        $this->expectException(ValidationException::class);

        $data = [
            'type' => 'AAAA',
            'name' => '@',
            'value' => 'not-an-ipv6',
            'ttl' => 3600,
        ];

        $this->service->addDnsRecord($this->domain, $data, $this->user->id);
    }

    public function test_validate_mx_record_requires_priority()
    {
        $this->expectException(ValidationException::class);

        $data = [
            'type' => 'MX',
            'name' => '@',
            'value' => 'mail.example.com',
            'ttl' => 3600,
        ];

        $this->service->addDnsRecord($this->domain, $data, $this->user->id);
    }

    public function test_validate_ttl_minimum()
    {
        $this->expectException(ValidationException::class);

        $data = [
            'type' => 'A',
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 30, // Below minimum
        ];

        $this->service->addDnsRecord($this->domain, $data, $this->user->id);
    }

    public function test_validate_ttl_maximum()
    {
        $this->expectException(ValidationException::class);

        $data = [
            'type' => 'A',
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 100000, // Above maximum
        ];

        $this->service->addDnsRecord($this->domain, $data, $this->user->id);
    }

    public function test_update_dns_record()
    {
        $record = DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        $data = [
            'type' => 'A',
            'name' => '@',
            'value' => '192.0.2.2',
            'ttl' => 7200,
        ];

        $result = $this->service->updateDnsRecord($record, $data, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('domain_dns_records', [
            'id' => $record->id,
            'value' => '192.0.2.2',
            'ttl' => 7200,
        ]);
    }

    public function test_delete_dns_record()
    {
        $record = DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        $result = $this->service->deleteDnsRecord($record, $this->user->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('domain_dns_records', [
            'id' => $record->id,
        ]);
    }

    public function test_get_dns_records_returns_all()
    {
        DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::MX,
            'name' => '@',
            'value' => 'mail.example.com',
            'ttl' => 3600,
            'priority' => 10,
        ]);

        $records = $this->service->getDnsRecords($this->domain);

        $this->assertCount(2, $records);
    }

    public function test_get_dns_records_filters_by_type()
    {
        DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::MX,
            'name' => '@',
            'value' => 'mail.example.com',
            'ttl' => 3600,
            'priority' => 10,
        ]);

        $records = $this->service->getDnsRecords($this->domain, DnsRecordType::A);

        $this->assertCount(1, $records);
        $this->assertEquals('A', $records[0]['type']->value);
    }

    public function test_add_dns_record_creates_audit_log()
    {
        $data = [
            'type' => 'A',
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ];

        $result = $this->service->addDnsRecord($this->domain, $data, $this->user->id);

        $this->assertDatabaseHas('audit_logs', [
            'partner_id' => $this->domain->partner_id,
            'user_id' => $this->user->id,
            'auditable_type' => DomainDnsRecord::class,
            'action' => 'dns_record_created',
        ]);
    }

    public function test_txt_record_validates_max_length()
    {
        $this->expectException(ValidationException::class);

        $data = [
            'type' => 'TXT',
            'name' => '@',
            'value' => str_repeat('a', 256), // Exceeds 255 chars
            'ttl' => 3600,
        ];

        $this->service->addDnsRecord($this->domain, $data, $this->user->id);
    }
}
