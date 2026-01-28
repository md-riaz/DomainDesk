<?php

namespace Tests\Feature\Jobs;

use App\Enums\DomainStatus;
use App\Jobs\ProcessDomainRegistrationJob;
use App\Jobs\SendEmailJob;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessDomainRegistrationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_processes_domain_registration_successfully(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        $registrar = Registrar::factory()->create();
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => $registrar->id,
            'status' => 'pending',
        ]);

        $job = new ProcessDomainRegistrationJob($domain);
        $job->handle();

        $domain->refresh();
        
        $this->assertEquals(DomainStatus::Active, $domain->status);
        $this->assertNotNull($domain->registered_at);
        $this->assertNotNull($domain->expires_at);
    }

    public function test_sends_confirmation_email_on_success(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        $registrar = Registrar::factory()->create();
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => $registrar->id,
            'status' => 'pending',
        ]);

        $job = new ProcessDomainRegistrationJob($domain);
        $job->handle();

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($client) {
            return $job->to === $client->email;
        });
    }

    public function test_updates_status_to_failed_after_max_attempts(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        // Domain without registrar to force failure
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => null,
            'status' => 'pending',
        ]);

        $job = new ProcessDomainRegistrationJob($domain);
        
        // Simulate max attempts
        for ($i = 0; $i < 3; $i++) {
            try {
                $job->handle();
            } catch (\Exception $e) {
                // Expected to fail
            }
        }

        $domain->refresh();
        $this->assertEquals(DomainStatus::RegistrationFailed, $domain->status);
    }

    public function test_handles_missing_client_gracefully(): void
    {
        $partner = Partner::factory()->create();
        $registrar = Registrar::factory()->create();
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => null,
            'registrar_id' => $registrar->id,
            'status' => 'pending',
        ]);

        $job = new ProcessDomainRegistrationJob($domain);
        $job->handle();

        $domain->refresh();
        $this->assertEquals(DomainStatus::Active, $domain->status);
        
        // Should not attempt to send email
        Queue::assertNotPushed(SendEmailJob::class);
    }

    public function test_retries_on_temporary_failure(): void
    {
        $partner = Partner::factory()->create();
        $client = User::factory()->create(['partner_id' => $partner->id, 'role' => 'client']);
        
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'registrar_id' => null, // Will cause failure
            'status' => 'pending',
        ]);

        $job = new ProcessDomainRegistrationJob($domain);

        try {
            $job->handle();
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('registrar', strtolower($e->getMessage()));
        }
    }

    public function test_job_has_correct_configuration(): void
    {
        $domain = Domain::factory()->create();
        $job = new ProcessDomainRegistrationJob($domain);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }
}
