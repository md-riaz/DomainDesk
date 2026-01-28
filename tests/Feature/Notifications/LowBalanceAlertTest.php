<?php

namespace Tests\Feature\Notifications;

use App\Models\Partner;
use App\Models\User;
use App\Notifications\LowBalanceAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowBalanceAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_correct_channels(): void
    {
        $partner = Partner::factory()->create();
        $notification = new LowBalanceAlert($partner, 50.00, 100.00);

        $user = User::factory()->create();
        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_mail_notification_contains_balance_details(): void
    {
        $partner = Partner::factory()->create(['name' => 'Acme Corp']);
        $user = User::factory()->create([
            'name' => 'John Admin',
            'partner_id' => $partner->id,
            'role' => 'partner',
        ]);

        $notification = new LowBalanceAlert($partner, 45.50, 100.00);
        $mail = $notification->toMail($user);

        $rendered = $mail->render();
        $this->assertStringContainsString('John Admin', $mail->greeting);
        $this->assertStringContainsString('Acme Corp', $mail->subject);
        $this->assertStringContainsString('45.50', $rendered);
        $this->assertStringContainsString('100.00', $rendered);
    }

    public function test_shows_critical_warning_for_zero_balance(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id, 'role' => 'partner']);

        $notification = new LowBalanceAlert($partner, 0.00, 100.00);
        $mail = $notification->toMail($user);

        $rendered = $mail->render();
        $this->assertStringContainsString('CRITICAL', $rendered);
        $this->assertStringContainsString('🚨', $rendered);
    }

    public function test_shows_warning_for_low_balance(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id, 'role' => 'partner']);

        $notification = new LowBalanceAlert($partner, 40.00, 100.00);
        $mail = $notification->toMail($user);

        $rendered = $mail->render();
        $this->assertStringContainsString('below 50%', $rendered);
    }

    public function test_includes_topup_action_button(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id, 'role' => 'partner']);

        $notification = new LowBalanceAlert($partner, 50.00, 100.00);
        $mail = $notification->toMail($user);

        $this->assertNotEmpty($mail->actionUrl);
        $this->assertStringContainsString('Top Up Wallet', $mail->actionText);
    }

    public function test_array_notification_contains_required_fields(): void
    {
        $partner = Partner::factory()->create(['name' => 'Test Partner']);
        $user = User::factory()->create();

        $notification = new LowBalanceAlert($partner, 75.00, 100.00);
        $array = $notification->toArray($user);

        $this->assertArrayHasKey('partner_id', $array);
        $this->assertArrayHasKey('partner_name', $array);
        $this->assertArrayHasKey('current_balance', $array);
        $this->assertArrayHasKey('threshold', $array);
        $this->assertArrayHasKey('percentage_of_threshold', $array);
        $this->assertArrayHasKey('severity', $array);
        $this->assertArrayHasKey('topup_url', $array);
        $this->assertArrayHasKey('transactions_url', $array);

        $this->assertEquals($partner->id, $array['partner_id']);
        $this->assertEquals('Test Partner', $array['partner_name']);
        $this->assertEquals(75.00, $array['current_balance']);
        $this->assertEquals(100.00, $array['threshold']);
        $this->assertEquals(75.0, $array['percentage_of_threshold']);
    }

    public function test_severity_is_critical_for_zero_balance(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create();

        $notification = new LowBalanceAlert($partner, 0.00, 100.00);
        $array = $notification->toArray($user);

        $this->assertEquals('critical', $array['severity']);
    }

    public function test_severity_is_high_for_below_25_percent(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create();

        $notification = new LowBalanceAlert($partner, 20.00, 100.00);
        $array = $notification->toArray($user);

        $this->assertEquals('high', $array['severity']);
    }

    public function test_severity_is_medium_for_below_50_percent(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create();

        $notification = new LowBalanceAlert($partner, 40.00, 100.00);
        $array = $notification->toArray($user);

        $this->assertEquals('medium', $array['severity']);
    }

    public function test_severity_is_low_for_above_50_percent(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create();

        $notification = new LowBalanceAlert($partner, 60.00, 100.00);
        $array = $notification->toArray($user);

        $this->assertEquals('low', $array['severity']);
    }

    public function test_calculates_percentage_correctly(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create();

        $notification = new LowBalanceAlert($partner, 33.33, 100.00);
        $array = $notification->toArray($user);

        $this->assertEquals(33.33, $array['percentage_of_threshold']);
    }

    public function test_mail_shows_percentage(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id, 'role' => 'partner']);

        $notification = new LowBalanceAlert($partner, 50.00, 100.00);
        $mail = $notification->toMail($user);

        $rendered = $mail->render();
        $this->assertStringContainsString('50', $rendered);
        $this->assertStringContainsString('%', $rendered);
    }
}
