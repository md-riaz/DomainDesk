<?php

namespace App\Jobs;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Notifications\DomainExpiryAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDomainRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public Domain $domain
    ) {}

    public function handle(): void
    {
        Log::info("Processing domain registration", [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Get registrar
            $registrar = $this->domain->registrar;
            
            if (!$registrar) {
                throw new \Exception("No registrar configured for domain");
            }

            // Simulate API call to registrar
            // In production, this would call the actual registrar API
            $this->callRegistrarAPI($registrar);

            // Update domain status
            $this->domain->update([
                'status' => DomainStatus::Active,
                'registered_at' => now(),
                'expires_at' => now()->addYear(),
            ]);

            // Send confirmation email
            if ($this->domain->client) {
                SendEmailJob::dispatch(
                    $this->domain->client->email,
                    'Domain Registration Successful',
                    'emails.domain-registration-success',
                    ['domain' => $this->domain]
                );
            }

            Log::info("Domain registration successful", [
                'domain_id' => $this->domain->id,
                'domain_name' => $this->domain->name,
            ]);

        } catch (\Exception $e) {
            Log::error("Domain registration failed", [
                'domain_id' => $this->domain->id,
                'domain_name' => $this->domain->name,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Update status on final failure
            if ($this->attempts() >= $this->tries) {
                $this->domain->update([
                    'status' => DomainStatus::Suspended, // Using Suspended as failure status
                ]);

                // Send failure email
                if ($this->domain->client) {
                    SendEmailJob::dispatch(
                        $this->domain->client->email,
                        'Domain Registration Failed',
                        'emails.domain-registration-failed',
                        ['domain' => $this->domain, 'error' => $e->getMessage()]
                    );
                }
            }

            throw $e;
        }
    }

    protected function callRegistrarAPI($registrar): void
    {
        // In production, implement actual registrar API call here
        // Example:
        // $client = app(RegistrarClientInterface::class);
        // $result = $client->registerDomain($this->domain);
        
        // For now, just log the attempt
        Log::info("Registrar API called", [
            'registrar_id' => $registrar->id,
            'domain_name' => $this->domain->name,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Domain registration job failed permanently", [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'error' => $exception->getMessage(),
        ]);
    }
}
