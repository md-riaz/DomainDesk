<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\System\SystemHealth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'partner_id' => null,
        ]);
    }

    public function test_super_admin_can_access_health()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.system.health'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(SystemHealth::class);
    }

    public function test_non_super_admin_cannot_access_health()
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $this->actingAs($partner);

        $response = $this->get(route('admin.system.health'));

        $response->assertStatus(403);
    }

    public function test_performs_health_checks_on_mount()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemHealth::class)
            ->assertSet('healthChecks.database.status', 'ok')
            ->assertSet('healthChecks.cache.status', 'ok')
            ->assertNotNull('lastChecked');
    }

    public function test_database_check_passes()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemHealth::class)
            ->assertSet('healthChecks.database.status', 'ok')
            ->assertSet('healthChecks.database.message', 'Connected');
    }

    public function test_cache_check_passes()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemHealth::class)
            ->assertSet('healthChecks.cache.status', 'ok')
            ->assertSet('healthChecks.cache.message', 'Working');
    }

    public function test_storage_check_passes()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemHealth::class)
            ->assertSet('healthChecks.storage.status', 'ok')
            ->assertSet('healthChecks.storage.message', 'Writable');
    }

    public function test_can_refresh_health_checks()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemHealth::class)
            ->call('refresh')
            ->assertSessionHas('success')
            ->assertNotNull('lastChecked');
    }

    public function test_displays_system_information()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.system.health'));

        $response->assertSee('Laravel Version');
        $response->assertSee('PHP Version');
        $response->assertSee('Cache Driver');
        $response->assertSee('Queue Driver');
    }

    public function test_health_check_includes_troubleshooting_for_warnings()
    {
        $this->actingAs($this->superAdmin);

        $component = Livewire::test(SystemHealth::class);

        foreach ($component->get('healthChecks') as $check) {
            if ($check['status'] === 'warning' || $check['status'] === 'error') {
                $this->assertNotNull($check['troubleshooting']);
            }
        }
    }

    public function test_queue_check_detects_sync_driver()
    {
        config(['queue.default' => 'sync']);

        $this->actingAs($this->superAdmin);

        Livewire::test(SystemHealth::class)
            ->assertSet('healthChecks.queue.status', 'warning')
            ->assertSet('healthChecks.queue.message', 'Using sync driver');
    }

    public function test_mail_check_detects_log_driver()
    {
        config(['mail.default' => 'log']);

        $this->actingAs($this->superAdmin);

        Livewire::test(SystemHealth::class)
            ->assertSet('healthChecks.mail.status', 'warning')
            ->assertSet('healthChecks.mail.message', 'Using log driver');
    }
}
