<?php

namespace Tests\Feature\Notifications;

use App\Models\Domain;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\DomainExpiryAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainExpiryAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_contains_correct_channels(): void
    {
        $domain = Domain::factory()->create();
        $notification = new DomainExpiryAlert($domain, 30);

        $user = User::factory()->create();
        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_mail_notification_has_correct_subject(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(30),
        ]);

        $notification = new DomainExpiryAlert($domain, 30);
        $mail = $notification->toMail($client);

        $this->assertStringContainsString('example.com', $mail->subject);
        $this->assertStringContainsString('Expiring Soon', $mail->subject);
    }

    public function test_mail_notification_contains_domain_details(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create([
            'name' => 'John Doe',
            'partner_id' => $partner->id,
            'role' => 'client',
        ]);
        
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(15),
        ]);

        $notification = new DomainExpiryAlert($domain, 15);
        $mail = $notification->toMail($client);

        $this->assertStringContainsString('John Doe', $mail->greeting);
        $this->assertStringContainsString('example.com', $mail->render());
        $this->assertStringContainsString('15', $mail->render());
    }

    public function test_mail_notification_has_renewal_action_button(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $notification = new DomainExpiryAlert($domain, 7);
        $mail = $notification->toMail($client);

        $this->assertNotEmpty($mail->actionUrl);
        $this->assertStringContainsString('Renew Domain', $mail->actionText);
    }

    public function test_array_notification_contains_required_fields(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'test.com',
            'expires_at' => now()->addDays(7),
            'auto_renew' => true,
        ]);

        $notification = new DomainExpiryAlert($domain, 7);
        $user = User::factory()->create();
        $array = $notification->toArray($user);

        $this->assertArrayHasKey('domain_id', $array);
        $this->assertArrayHasKey('domain_name', $array);
        $this->assertArrayHasKey('days_until_expiry', $array);
        $this->assertArrayHasKey('expires_at', $array);
        $this->assertArrayHasKey('urgency', $array);
        $this->assertArrayHasKey('auto_renew', $array);
        $this->assertArrayHasKey('action_url', $array);

        $this->assertEquals($domain->id, $array['domain_id']);
        $this->assertEquals('test.com', $array['domain_name']);
        $this->assertEquals(7, $array['days_until_expiry']);
        $this->assertTrue($array['auto_renew']);
    }

    public function test_urgency_level_is_critical_for_1_day(): void
    {
        $domain = Domain::factory()->create(['expires_at' => now()->addDay()]);
        $notification = new DomainExpiryAlert($domain, 1);
        
        $user = User::factory()->create();
        $array = $notification->toArray($user);

        $this->assertEquals('critical', $array['urgency']);
    }

    public function test_urgency_level_is_high_for_7_days(): void
    {
        $domain = Domain::factory()->create(['expires_at' => now()->addDays(7)]);
        $notification = new DomainExpiryAlert($domain, 7);
        
        $user = User::factory()->create();
        $array = $notification->toArray($user);

        $this->assertEquals('high', $array['urgency']);
    }

    public function test_urgency_level_is_medium_for_15_days(): void
    {
        $domain = Domain::factory()->create(['expires_at' => now()->addDays(15)]);
        $notification = new DomainExpiryAlert($domain, 15);
        
        $user = User::factory()->create();
        $array = $notification->toArray($user);

        $this->assertEquals('medium', $array['urgency']);
    }

    public function test_urgency_level_is_low_for_30_days(): void
    {
        $domain = Domain::factory()->create(['expires_at' => now()->addDays(30)]);
        $notification = new DomainExpiryAlert($domain, 30);
        
        $user = User::factory()->create();
        $array = $notification->toArray($user);

        $this->assertEquals('low', $array['urgency']);
    }

    public function test_shows_auto_renew_status_in_mail(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'expires_at' => now()->addDays(7),
            'auto_renew' => true,
        ]);

        $notification = new DomainExpiryAlert($domain, 7);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('Auto-renewal', $rendered);
    }

    public function test_critical_urgency_shows_warning_in_mail(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'expires_at' => now()->addDay(),
        ]);

        $notification = new DomainExpiryAlert($domain, 1);
        $mail = $notification->toMail($client);

        $rendered = $mail->render();
        $this->assertStringContainsString('URGENT', $rendered);
    }
}
