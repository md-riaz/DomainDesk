<?php

namespace Tests\Feature\Livewire\Partner;

use App\Livewire\Partner\Settings\BrandingSettings;
use App\Models\Partner;
use App\Models\PartnerBranding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $partnerUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->partner = Partner::factory()->create();
        $this->partnerUser = User::factory()->partner()->create([
            'partner_id' => $this->partner->id,
        ]);
    }

    public function test_component_renders(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->assertOk();
    }

    public function test_loads_existing_branding_data(): void
    {
        $branding = PartnerBranding::create([
            'partner_id' => $this->partner->id,
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'email_sender_name' => 'Test Company',
            'email_sender_email' => 'test@example.com',
            'support_email' => 'support@example.com',
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->assertSet('primaryColor', '#FF0000')
            ->assertSet('secondaryColor', '#00FF00')
            ->assertSet('emailSenderName', 'Test Company')
            ->assertSet('emailSenderEmail', 'test@example.com')
            ->assertSet('supportEmail', 'support@example.com');
    }

    public function test_can_upload_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 50);

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('logo', $file)
            ->set('primaryColor', '#3B82F6')
            ->set('secondaryColor', '#10B981')
            ->set('emailSenderName', 'Test Company')
            ->set('emailSenderEmail', 'test@example.com')
            ->set('supportEmail', 'support@example.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSessionHas('message');

        $branding = $this->partner->fresh()->branding;
        $this->assertNotNull($branding->logo_path);
        Storage::disk('public')->assertExists($branding->logo_path);
    }

    public function test_can_upload_favicon(): void
    {
        $file = UploadedFile::fake()->image('favicon.png', 32, 32);

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('favicon', $file)
            ->set('primaryColor', '#3B82F6')
            ->set('secondaryColor', '#10B981')
            ->set('emailSenderName', 'Test Company')
            ->set('emailSenderEmail', 'test@example.com')
            ->set('supportEmail', 'support@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $branding = $this->partner->fresh()->branding;
        $this->assertNotNull($branding->favicon_path);
        Storage::disk('public')->assertExists($branding->favicon_path);
    }

    public function test_validates_logo_file_size(): void
    {
        $file = UploadedFile::fake()->create('logo.png', 3000); // 3MB

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('logo', $file)
            ->assertHasErrors(['logo' => 'max']);
    }

    public function test_validates_favicon_file_size(): void
    {
        $file = UploadedFile::fake()->create('favicon.png', 150); // 150KB

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('favicon', $file)
            ->assertHasErrors(['favicon' => 'max']);
    }

    public function test_validates_primary_color_format(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('primaryColor', 'invalid')
            ->set('emailSenderName', 'Test')
            ->set('emailSenderEmail', 'test@example.com')
            ->set('supportEmail', 'support@example.com')
            ->call('save')
            ->assertHasErrors(['primaryColor']);
    }

    public function test_validates_email_formats(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('emailSenderEmail', 'invalid-email')
            ->set('supportEmail', 'invalid-email')
            ->set('primaryColor', '#3B82F6')
            ->set('secondaryColor', '#10B981')
            ->set('emailSenderName', 'Test')
            ->call('save')
            ->assertHasErrors(['emailSenderEmail', 'supportEmail']);
    }

    public function test_can_remove_logo(): void
    {
        $branding = PartnerBranding::create([
            'partner_id' => $this->partner->id,
            'logo_path' => 'partner-1/branding/logo.png',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#10B981',
            'email_sender_name' => 'Test',
            'email_sender_email' => 'test@example.com',
            'support_email' => 'support@example.com',
        ]);

        Storage::disk('public')->put('partner-1/branding/logo.png', 'fake-content');

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->call('removeLogo')
            ->assertSessionHas('message');

        $this->assertNull($branding->fresh()->logo_path);
    }

    public function test_can_remove_favicon(): void
    {
        $branding = PartnerBranding::create([
            'partner_id' => $this->partner->id,
            'favicon_path' => 'partner-1/branding/favicon.png',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#10B981',
            'email_sender_name' => 'Test',
            'email_sender_email' => 'test@example.com',
            'support_email' => 'support@example.com',
        ]);

        Storage::disk('public')->put('partner-1/branding/favicon.png', 'fake-content');

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->call('removeFavicon')
            ->assertSessionHas('message');

        $this->assertNull($branding->fresh()->favicon_path);
    }

    public function test_can_reset_colors(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('primaryColor', '#FF0000')
            ->set('secondaryColor', '#00FF00')
            ->call('resetColors')
            ->assertSet('primaryColor', '#3B82F6')
            ->assertSet('secondaryColor', '#10B981')
            ->assertSessionHas('message');
    }

    public function test_can_toggle_preview(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->assertSet('showPreview', false)
            ->call('togglePreview')
            ->assertSet('showPreview', true)
            ->call('togglePreview')
            ->assertSet('showPreview', false);
    }

    public function test_saves_all_branding_settings(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('primaryColor', '#FF5733')
            ->set('secondaryColor', '#33FF57')
            ->set('emailSenderName', 'My Company')
            ->set('emailSenderEmail', 'sender@mycompany.com')
            ->set('replyToEmail', 'reply@mycompany.com')
            ->set('supportEmail', 'support@mycompany.com')
            ->set('supportPhone', '+1234567890')
            ->set('supportUrl', 'https://support.mycompany.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSessionHas('message');

        $branding = $this->partner->fresh()->branding;
        $this->assertEquals('#FF5733', $branding->primary_color);
        $this->assertEquals('#33FF57', $branding->secondary_color);
        $this->assertEquals('My Company', $branding->email_sender_name);
        $this->assertEquals('sender@mycompany.com', $branding->email_sender_email);
        $this->assertEquals('reply@mycompany.com', $branding->reply_to_email);
        $this->assertEquals('support@mycompany.com', $branding->support_email);
        $this->assertEquals('+1234567890', $branding->support_phone);
        $this->assertEquals('https://support.mycompany.com', $branding->support_url);
    }

    public function test_updates_existing_branding(): void
    {
        $branding = PartnerBranding::create([
            'partner_id' => $this->partner->id,
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
            'email_sender_name' => 'Old Name',
            'email_sender_email' => 'old@example.com',
            'support_email' => 'old-support@example.com',
        ]);

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('primaryColor', '#FF0000')
            ->set('emailSenderName', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $branding->refresh();
        $this->assertEquals('#FF0000', $branding->primary_color);
        $this->assertEquals('New Name', $branding->email_sender_name);
    }

    public function test_validates_support_url_format(): void
    {
        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('supportUrl', 'not-a-valid-url')
            ->set('primaryColor', '#3B82F6')
            ->set('secondaryColor', '#10B981')
            ->set('emailSenderName', 'Test')
            ->set('emailSenderEmail', 'test@example.com')
            ->set('supportEmail', 'support@example.com')
            ->call('save')
            ->assertHasErrors(['supportUrl']);
    }

    public function test_replaces_old_logo_when_uploading_new_one(): void
    {
        $oldLogo = 'partner-1/branding/old-logo.png';
        Storage::disk('public')->put($oldLogo, 'old-content');

        $branding = PartnerBranding::create([
            'partner_id' => $this->partner->id,
            'logo_path' => $oldLogo,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#10B981',
            'email_sender_name' => 'Test',
            'email_sender_email' => 'test@example.com',
            'support_email' => 'support@example.com',
        ]);

        $newLogo = UploadedFile::fake()->image('new-logo.png');

        Livewire::actingAs($this->partnerUser)
            ->test(BrandingSettings::class)
            ->set('logo', $newLogo)
            ->call('save');

        Storage::disk('public')->assertMissing($oldLogo);
        $this->assertNotEquals($oldLogo, $branding->fresh()->logo_path);
    }
}
