<?php

namespace Tests\Feature\Notifications;

use App\Models\Domain;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\RenewalReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenewalReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_correct_channels(): void
    {
        $domain = Domain::factory()->create();
        $notification = new RenewalReminder($domain, 30);

        $user = User::factory()->create();
        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_mail_notification_contains_domain_details(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'name' => 'Jane Doe',
            'partner_id' => $partner->id,
            'role' => 'client',
        ]);
        
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(30),
        ]);

        $notification = new RenewalReminder($domain, 30);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('Jane Doe', $mail->greeting);
        $this->assertStringContainsString('example.com', $rendered);
        $this->assertStringContainsString('30', $rendered);
    }

    public function test_critical_urgency_for_1_day(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'expires_at' => now()->addDay(),
        ]);

        $notification = new RenewalReminder($domain, 1);
        $mail = $notification->toMail($client);

        $this->assertStringContainsString('CRITICAL', $mail->render());
        $this->assertStringContainsString('🚨', $mail->subject);
    }

    public function test_high_urgency_for_7_days(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $notification = new RenewalReminder($domain, 7);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('IMPORTANT', $rendered);
        $this->assertStringContainsString('⚠️', $mail->subject);
    }

    public function test_shows_auto_renew_enabled_message(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(15),
            'auto_renew' => true,
        ]);

        $notification = new RenewalReminder($domain, 15);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('Auto-renewal', $rendered);
        $this->assertStringContainsString('enabled', $rendered);
    }

    public function test_shows_auto_renew_disabled_message(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(15),
            'auto_renew' => false,
        ]);

        $notification = new RenewalReminder($domain, 15);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('not enabled', $rendered);
    }

    public function test_includes_renew_action_when_auto_renew_disabled(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(15),
            'auto_renew' => false,
        ]);

        $notification = new RenewalReminder($domain, 15);
        $mail = $notification->toMail($client);

        $this->assertNotEmpty($mail->actionUrl);
        $this->assertStringContainsString('Renew Now', $mail->actionText);
    }

    public function test_array_notification_contains_required_fields(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'test.com',
            'expires_at' => now()->addDays(7),
            'auto_renew' => false,
        ]);

        $notification = new RenewalReminder($domain, 7);
        $user = User::factory()->create();
        $array = $notification->toArray($user);

        $this->assertArrayHasKey('domain_id', $array);
        $this->assertArrayHasKey('domain_name', $array);
        $this->assertArrayHasKey('days_until_expiry', $array);
        $this->assertArrayHasKey('expires_at', $array);
        $this->assertArrayHasKey('urgency', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('auto_renew', $array);
        $this->assertArrayHasKey('renew_url', $array);
        $this->assertArrayHasKey('details_url', $array);
    }

    public function test_subject_varies_by_urgency(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create(['partner_id' => $partner->id]);

        // Critical (1 day)
        $domain->expires_at = now()->addDay();
        $notification = new RenewalReminder($domain, 1);
        $mail = $notification->toMail($client);
        $this->assertStringContainsString('URGENT', $mail->subject);

        // High (7 days)
        $domain->expires_at = now()->addDays(7);
        $notification = new RenewalReminder($domain, 7);
        $mail = $notification->toMail($client);
        $this->assertStringContainsString('Important', $mail->subject);

        // Medium (15 days)
        $domain->expires_at = now()->addDays(15);
        $notification = new RenewalReminder($domain, 15);
        $mail = $notification->toMail($client);
        $this->assertStringContainsString('Reminder', $mail->subject);
    }
}
