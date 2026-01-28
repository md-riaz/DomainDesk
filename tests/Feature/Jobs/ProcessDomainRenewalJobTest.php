<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessDomainRenewalJob;
use App\Jobs\SendEmailJob;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessDomainRenewalJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_processes_domain_renewal_successfully(): void
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(100, 'Initial balance');
        
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        $registrar = Registrar::factory()->create();
        
        $originalExpiry = now()->addDays(7);
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => $registrar->id,
            'expires_at' => $originalExpiry,
            'status' => 'active',
        ]);

        $job = new ProcessDomainRenewalJob($domain);
        $job->handle();

        $domain->refresh();
        
        // Should extend expiry by 1 year
        $this->assertEquals(
            $originalExpiry->copy()->addYear()->format('Y-m-d'),
            $domain->expires_at->format('Y-m-d')
        );
    }

    public function test_debits_wallet_on_successful_renewal(): void
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(100, 'Initial balance');
        
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        $registrar = Registrar::factory()->create();
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => $registrar->id,
            'expires_at' => now()->addDays(7),
            'status' => 'active',
        ]);

        $initialBalance = $wallet->balance;
        
        $job = new ProcessDomainRenewalJob($domain);
        $job->handle();

        $wallet->refresh();
        $this->assertLessThan($initialBalance, $wallet->balance);
    }

    public function test_sends_confirmation_email_on_success(): void
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        $wallet->credit(100, 'Initial balance');
        
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        $registrar = Registrar::factory()->create();
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => $registrar->id,
            'expires_at' => now()->addDays(7),
            'status' => 'active',
        ]);

        $job = new ProcessDomainRenewalJob($domain);
        $job->handle();

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($client) {
            return $job->to === $client->email &&
                   str_contains($job->subject, 'Renewed Successfully');
        });
    }

    public function test_fails_with_insufficient_balance(): void
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);
        // No balance
        
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        $registrar = Registrar::factory()->create();
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => $registrar->id,
            'expires_at' => now()->addDays(7),
            'status' => 'active',
        ]);

        $job = new ProcessDomainRenewalJob($domain);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient wallet balance');

        $job->handle();
    }

    public function test_sends_failure_email_on_final_attempt(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        // Domain without registrar to force failure
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => null,
            'expires_at' => now()->addDays(7),
            'status' => 'active',
        ]);

        $job = new ProcessDomainRenewalJob($domain);
        
        // Simulate max attempts
        for ($i = 0; $i < 3; $i++) {
            try {
                $job->handle();
            } catch (\Exception $e) {
                // Expected
            }
        }

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($client) {
            return $job->to === $client->email &&
                   str_contains($job->subject, 'Failed');
        });
    }

    public function test_handles_missing_partner_wallet(): void
    {
        $partner = Partner::factory()->create();
        // No wallet created
        
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        $registrar = Registrar::factory()->create();
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => $registrar->id,
            'expires_at' => now()->addDays(7),
            'status' => 'active',
        ]);

        $job = new ProcessDomainRenewalJob($domain);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Partner wallet not found');

        $job->handle();
    }

    public function test_job_has_correct_configuration(): void
    {
        $domain = Domain::factory()->create();
        $job = new ProcessDomainRenewalJob($domain);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }
}
