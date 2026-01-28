<?php

namespace Tests\Feature\Commands;

use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\LowBalanceAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendLowBalanceAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_sends_alerts_for_low_balance_partners(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(50, 'Initial balance'); // Below default threshold of 100
        
        $admin = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => 'partner',
        ]);

        $this->artisan('partners:send-low-balance-alerts')
            ->assertExitCode(0);

        Notification::assertSentTo($admin, LowBalanceAlert::class);
    }

    public function test_does_not_send_alerts_for_sufficient_balance(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(500, 'Initial balance'); // Above threshold
        
        $admin = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => 'partner',
        ]);

        $this->artisan('partners:send-low-balance-alerts')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_sends_alerts_to_all_admin_users(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(50, 'Initial balance');
        
        $admins = User::factory()->count(3)->create([
            'partner_id' => $partner->id,
            'role' => 'partner',
        ]);

        $this->artisan('partners:send-low-balance-alerts')
            ->assertExitCode(0);

        foreach ($admins as $admin) {
            Notification::assertSentTo($admin, LowBalanceAlert::class);
        }
    }

    public function test_does_not_send_alerts_to_client_users(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(50, 'Initial balance');
        
        $admin = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => 'partner',
        ]);
        
        $client = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => 'client',
        ]);

        $this->artisan('partners:send-low-balance-alerts')
            ->assertExitCode(0);

        Notification::assertSentTo($admin, LowBalanceAlert::class);
        Notification::assertNotSentTo($client, LowBalanceAlert::class);
    }

    public function test_handles_partners_without_wallet(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        // No wallet created
        
        $admin = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => 'partner',
        ]);

        $this->artisan('partners:send-low-balance-alerts')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_handles_partners_without_admin_users(): void
    {
        $partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(50, 'Initial balance');
        // No admin users created

        $this->artisan('partners:send-low-balance-alerts')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_ignores_inactive_partners(): void
    {
        $partner = Partner::factory()->create(['is_active' => false]);
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(50, 'Initial balance');
        
        $admin = User::factory()->create([
            'partner_id' => $partner->id,
            'role' => 'partner',
        ]);

        $this->artisan('partners:send-low-balance-alerts')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_handles_multiple_partners(): void
    {
        // Partner 1 - Low balance
        $partner1 = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet1 = Wallet::factory()->create(['partner_id' => $partner1->id]);
        $wallet1->credit(50, 'Initial balance');
        $admin1 = User::factory()->create(['partner_id' => $partner1->id, 'role' => 'partner']);

        // Partner 2 - Sufficient balance
        $partner2 = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $wallet2 = Wallet::factory()->create(['partner_id' => $partner2->id]);
        $wallet2->credit(500, 'Initial balance');
        $admin2 = User::factory()->create(['partner_id' => $partner2->id, 'role' => 'partner']);

        $this->artisan('partners:send-low-balance-alerts')
            ->assertExitCode(0);

        Notification::assertSentTo($admin1, LowBalanceAlert::class);
        Notification::assertNotSentTo($admin2, LowBalanceAlert::class);
    }
}
