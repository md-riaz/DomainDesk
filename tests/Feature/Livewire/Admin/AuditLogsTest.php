<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\System\AuditLogs;
use App\Models\AuditLog;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'partner_id' => null,
        ]);

        $this->partner = Partner::factory()->create();
    }

    public function test_super_admin_can_access_audit_logs()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.system.audit-logs'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(AuditLogs::class);
    }

    public function test_non_super_admin_cannot_access_audit_logs()
    {
        $partner = User::factory()->create(['role' => 'partner', 'partner_id' => $this->partner->id]);
        $this->actingAs($partner);

        $response = $this->get(route('admin.system.audit-logs'));

        $response->assertStatus(403);
    }

    public function test_displays_audit_logs()
    {
        $this->actingAs($this->superAdmin);

        $auditLog = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'partner_id' => null,
            'action' => 'created',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'old_values' => null,
            'new_values' => ['name' => 'Test Partner'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        Livewire::test(AuditLogs::class)
            ->assertSee($this->superAdmin->email)
            ->assertSee('created')
            ->assertSee('Partner');
    }

    public function test_can_search_audit_logs()
    {
        $this->actingAs($this->superAdmin);

        $auditLog1 = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'created',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $auditLog2 = AuditLog::create([
            'user_id' => $otherUser->id,
            'action' => 'updated',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        Livewire::test(AuditLogs::class)
            ->set('search', 'other@example.com')
            ->assertSee('other@example.com')
            ->assertDontSee($this->superAdmin->email);
    }

    public function test_can_filter_by_action()
    {
        $this->actingAs($this->superAdmin);

        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'created',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'updated',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        $component = Livewire::test(AuditLogs::class)
            ->set('filterAction', 'created');

        // Check that we only see 1 log (the created one)
        $logs = $component->viewData('logs');
        $this->assertEquals(1, $logs->count());
        $this->assertEquals('created', $logs->first()->action);
    }

    public function test_can_filter_by_model_type()
    {
        $this->actingAs($this->superAdmin);

        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'created',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => $this->superAdmin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        $component = Livewire::test(AuditLogs::class)
            ->set('filterModel', Partner::class);

        // Check that we only see 1 log (the Partner one)
        $logs = $component->viewData('logs');
        $this->assertEquals(1, $logs->count());
        $this->assertEquals(Partner::class, $logs->first()->auditable_type);
    }

    public function test_can_filter_by_date_range()
    {
        $this->actingAs($this->superAdmin);

        $oldLog = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'created',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'created_at' => now()->subDays(60),
        ]);

        $newLog = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'updated',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'created_at' => now(),
        ]);

        $component = Livewire::test(AuditLogs::class)
            ->set('dateFrom', now()->subDays(7)->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'));

        // Check that we only see 1 log (the recent one)
        $logs = $component->viewData('logs');
        $this->assertEquals(1, $logs->count());
        $this->assertEquals('updated', $logs->first()->action);
    }

    public function test_can_reset_filters()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(AuditLogs::class)
            ->set('search', 'test')
            ->set('filterAction', 'created')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('filterAction', '');
    }

    public function test_can_view_details()
    {
        $this->actingAs($this->superAdmin);

        $auditLog = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'created',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'old_values' => null,
            'new_values' => ['name' => 'Test Partner'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        Livewire::test(AuditLogs::class)
            ->call('viewDetails', $auditLog->id)
            ->assertSet('showDetailsModal', true)
            ->assertSet('selectedLog.id', $auditLog->id);
    }

    public function test_can_close_details_modal()
    {
        $this->actingAs($this->superAdmin);

        $auditLog = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'created',
            'auditable_type' => Partner::class,
            'auditable_id' => $this->partner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        Livewire::test(AuditLogs::class)
            ->call('viewDetails', $auditLog->id)
            ->call('closeDetailsModal')
            ->assertSet('showDetailsModal', false)
            ->assertSet('selectedLog', null);
    }

    public function test_can_toggle_auto_refresh()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(AuditLogs::class)
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true);
    }

    public function test_pagination_works()
    {
        $this->actingAs($this->superAdmin);

        // Create more than 50 logs
        for ($i = 0; $i < 60; $i++) {
            AuditLog::create([
                'user_id' => $this->superAdmin->id,
                'action' => 'created',
                'auditable_type' => Partner::class,
                'auditable_id' => $this->partner->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Agent',
            ]);
        }

        Livewire::test(AuditLogs::class)
            ->assertSee('Next')
            ->assertSee('Previous');
    }
}
