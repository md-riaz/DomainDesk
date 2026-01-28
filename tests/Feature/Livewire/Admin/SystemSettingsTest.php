<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\System\SystemSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
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

    public function test_super_admin_can_access_settings()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.system.settings'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(SystemSettings::class);
    }

    public function test_non_super_admin_cannot_access_settings()
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $this->actingAs($partner);

        $response = $this->get(route('admin.system.settings'));

        $response->assertStatus(403);
    }

    public function test_can_save_general_settings()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('site_name', 'My Domain Desk')
            ->set('admin_email', 'admin@example.com')
            ->set('default_timezone', 'America/New_York')
            ->set('default_currency', 'USD')
            ->set('date_format', 'Y-m-d')
            ->set('time_format', 'H:i:s')
            ->call('saveGeneralSettings')
            ->assertHasNoErrors()
            ->assertSessionHas('success');

        $this->assertEquals('My Domain Desk', Setting::get('site_name'));
        $this->assertEquals('admin@example.com', Setting::get('admin_email'));
    }

    public function test_general_settings_validation()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('site_name', '')
            ->set('admin_email', 'invalid-email')
            ->call('saveGeneralSettings')
            ->assertHasErrors(['site_name', 'admin_email']);
    }

    public function test_can_save_email_settings()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('smtp_host', 'smtp.example.com')
            ->set('smtp_port', 587)
            ->set('smtp_username', 'user@example.com')
            ->set('smtp_password', 'password123')
            ->set('smtp_encryption', 'tls')
            ->set('mail_from_address', 'noreply@example.com')
            ->set('mail_from_name', 'DomainDesk')
            ->call('saveEmailSettings')
            ->assertHasNoErrors()
            ->assertSessionHas('success');

        $this->assertEquals('smtp.example.com', Setting::get('smtp_host'));
        $this->assertEquals(587, Setting::get('smtp_port'));
    }

    public function test_email_settings_validation()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('smtp_host', '')
            ->set('smtp_port', 99999) // Invalid port
            ->set('smtp_encryption', 'invalid')
            ->call('saveEmailSettings')
            ->assertHasErrors(['smtp_host', 'smtp_port', 'smtp_encryption']);
    }

    public function test_can_save_domain_settings()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('default_nameserver_1', 'ns1.example.com')
            ->set('default_nameserver_2', 'ns2.example.com')
            ->set('default_ttl', 3600)
            ->set('auto_renewal_lead_time', 30)
            ->set('grace_period_days', 15)
            ->call('saveDomainSettings')
            ->assertHasNoErrors()
            ->assertSessionHas('success');

        $this->assertEquals('ns1.example.com', Setting::get('default_nameserver_1'));
        $this->assertEquals(3600, Setting::get('default_ttl'));
    }

    public function test_domain_settings_validation()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('default_nameserver_1', '')
            ->set('default_ttl', 30) // Too low
            ->set('auto_renewal_lead_time', 500) // Too high
            ->call('saveDomainSettings')
            ->assertHasErrors(['default_nameserver_1', 'default_ttl', 'auto_renewal_lead_time']);
    }

    public function test_can_save_billing_settings()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('currency_symbol', '$')
            ->set('tax_rate', 10.5)
            ->set('invoice_prefix', 'INV-')
            ->set('low_balance_threshold', 100)
            ->call('saveBillingSettings')
            ->assertHasNoErrors()
            ->assertSessionHas('success');

        $this->assertEquals('$', Setting::get('currency_symbol'));
        $this->assertEquals(10.5, Setting::get('tax_rate'));
    }

    public function test_billing_settings_validation()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('currency_symbol', '')
            ->set('tax_rate', -5) // Negative
            ->set('invoice_prefix', '')
            ->call('saveBillingSettings')
            ->assertHasErrors(['currency_symbol', 'tax_rate', 'invoice_prefix']);
    }

    public function test_can_switch_tabs()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->assertSet('activeTab', 'general')
            ->call('switchTab', 'email')
            ->assertSet('activeTab', 'email')
            ->call('switchTab', 'domain')
            ->assertSet('activeTab', 'domain');
    }

    public function test_can_reset_to_defaults()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SystemSettings::class)
            ->set('site_name', 'Custom Name')
            ->call('resetToDefaults')
            ->assertSet('site_name', 'DomainDesk')
            ->assertSessionHas('info');
    }

    public function test_smtp_password_is_encrypted()
    {
        $this->actingAs($this->superAdmin);

        $password = 'secret123';

        Livewire::test(SystemSettings::class)
            ->set('smtp_host', 'smtp.example.com')
            ->set('smtp_port', 587)
            ->set('smtp_username', 'user@example.com')
            ->set('smtp_password', $password)
            ->set('smtp_encryption', 'tls')
            ->set('mail_from_address', 'noreply@example.com')
            ->set('mail_from_name', 'DomainDesk')
            ->call('saveEmailSettings');

        $setting = Setting::where('key', 'smtp_password')->first();
        $this->assertNotEquals($password, $setting->value);
        $this->assertEquals($password, Setting::get('smtp_password'));
    }

    public function test_loads_existing_settings()
    {
        $this->actingAs($this->superAdmin);

        Setting::set('site_name', 'Existing Site', 'string', 'general');
        Setting::set('admin_email', 'existing@example.com', 'string', 'general');

        Livewire::test(SystemSettings::class)
            ->assertSet('site_name', 'Existing Site')
            ->assertSet('admin_email', 'existing@example.com');
    }
}
