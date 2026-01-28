<?php

namespace Tests\Feature\Livewire;

use App\Enums\DnsRecordType;
use App\Livewire\Client\Domain\ManageDns;
use App\Models\Domain;
use App\Models\DomainDnsRecord;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageDnsTest extends TestCase
{
    use RefreshDatabase;

    protected Domain $domain;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
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

        // Set partner context
        session(['partner_id' => $partner->id]);
    }

    public function test_component_renders_successfully()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->assertStatus(200)
            ->assertSee('Manage DNS Records')
            ->assertSee($this->domain->name);
    }

    public function test_displays_existing_dns_records()
    {
        DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->assertSee('192.0.2.1');
    }

    public function test_can_open_add_modal()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openAddModal')
            ->assertSet('showAddModal', true);
    }

    public function test_can_close_add_modal()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openAddModal')
            ->call('closeAddModal')
            ->assertSet('showAddModal', false);
    }

    public function test_can_add_a_record()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openAddModal')
            ->set('recordType', 'A')
            ->set('recordName', '@')
            ->set('recordValue', '192.0.2.1')
            ->set('recordTtl', 3600)
            ->call('addRecord')
            ->assertSet('successMessage', function ($message) {
                return str_contains($message, 'successfully');
            });

        $this->assertDatabaseHas('domain_dns_records', [
            'domain_id' => $this->domain->id,
            'type' => 'A',
            'value' => '192.0.2.1',
        ]);
    }

    public function test_can_add_mx_record_with_priority()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openAddModal')
            ->set('recordType', 'MX')
            ->set('recordName', '@')
            ->set('recordValue', 'mail.example.com')
            ->set('recordTtl', 3600)
            ->set('recordPriority', 10)
            ->call('addRecord')
            ->assertSet('successMessage', function ($message) {
                return str_contains($message, 'successfully');
            });

        $this->assertDatabaseHas('domain_dns_records', [
            'domain_id' => $this->domain->id,
            'type' => 'MX',
            'priority' => 10,
        ]);
    }

    public function test_can_open_edit_modal()
    {
        $record = DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openEditModal', $record->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingRecordId', $record->id)
            ->assertSet('recordValue', '192.0.2.1');
    }

    public function test_can_update_dns_record()
    {
        $record = DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openEditModal', $record->id)
            ->set('recordValue', '192.0.2.2')
            ->call('updateRecord')
            ->assertSet('successMessage', function ($message) {
                return str_contains($message, 'successfully');
            });

        $this->assertDatabaseHas('domain_dns_records', [
            'id' => $record->id,
            'value' => '192.0.2.2',
        ]);
    }

    public function test_can_open_delete_modal()
    {
        $record = DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openDeleteModal', $record->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('deletingRecordId', $record->id);
    }

    public function test_can_delete_dns_record()
    {
        $record = DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openDeleteModal', $record->id)
            ->call('deleteRecord')
            ->assertSet('successMessage', function ($message) {
                return str_contains($message, 'successfully');
            });

        $this->assertDatabaseMissing('domain_dns_records', [
            'id' => $record->id,
        ]);
    }

    public function test_can_filter_by_record_type()
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

        $this->actingAs($this->user);

        $component = Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('filterByType', 'A');

        $records = $component->get('dnsRecords');
        $this->assertCount(1, $records);
        $this->assertEquals('A', $records[0]['type']->value);
    }

    public function test_shows_validation_error_for_invalid_ip()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('openAddModal')
            ->set('recordType', 'A')
            ->set('recordName', '@')
            ->set('recordValue', 'not-an-ip')
            ->set('recordTtl', 3600)
            ->call('addRecord')
            ->assertSet('errorMessage', function ($message) {
                return !empty($message);
            });
    }

    public function test_can_sync_dns_records()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->call('sync')
            ->assertSet('isSyncing', false);
    }

    public function test_resets_priority_when_changing_type()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->set('recordType', 'MX')
            ->set('recordPriority', 10)
            ->set('recordType', 'A')
            ->assertSet('recordPriority', null);
    }

    public function test_unauthorized_user_cannot_access()
    {
        $otherUser = User::factory()->client()->create();
        $this->actingAs($otherUser);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ManageDns::class, ['domain' => $this->domain]);
    }

    public function test_displays_info_banner()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->assertSee('TTL');
    }

    public function test_shows_empty_state_when_no_records()
    {
        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->assertSee('No DNS records found');
    }

    public function test_displays_record_type_badges()
    {
        DomainDnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => DnsRecordType::A,
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ManageDns::class, ['domain' => $this->domain])
            ->assertSee('bg-blue-100'); // Badge color for A records
    }
}
