<?php

namespace App\Jobs;

use App\Enums\DomainStatus;
use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDomainRenewalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public Domain $domain
    ) {}

    public function handle(): void
    {
        Log::info("Processing domain renewal", [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'attempt' => $this->attempts(),
        ]);

        try {
            $registrar = $this->domain->registrar;
            
            if (!$registrar) {
                throw new \Exception("No registrar configured for domain");
            }

            $partner = $this->domain->partner;
            
            if (!$partner || !$partner->wallet) {
                throw new \Exception("Partner wallet not found");
            }

            // Calculate renewal cost
            $renewalCost = 15.00; // Placeholder - should come from pricing

            // Check balance
            if ($partner->wallet->balance < $renewalCost) {
                throw new \Exception("Insufficient wallet balance");
            }

            // Debit wallet
            $partner->wallet->debit(
                $renewalCost,
                "Domain renewal: {$this->domain->name}",
                'domain',
                $this->domain->id
            );

            // Call registrar API
            $this->callRegistrarAPI($registrar);

            // Update domain expiry
            $newExpiryDate = $this->domain->expires_at->addYear();
            
            $this->domain->update([
                'expires_at' => $newExpiryDate,
                'status' => DomainStatus::Active,
            ]);

            // Send confirmation email
            if ($this->domain->client) {
                SendEmailJob::dispatch(
                    $this->domain->client->email,
                    'Domain Renewed Successfully',
                    'emails.domain-renewal-success',
                    [
                        'domain' => $this->domain,
                        'new_expiry_date' => $newExpiryDate,
                        'cost' => $renewalCost,
                    ]
                );
            }

            Log::info("Domain renewal successful", [
                'domain_id' => $this->domain->id,
                'domain_name' => $this->domain->name,
                'new_expiry_date' => $newExpiryDate,
                'cost' => $renewalCost,
            ]);

        } catch (\Exception $e) {
            Log::error("Domain renewal failed", [
                'domain_id' => $this->domain->id,
                'domain_name' => $this->domain->name,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Send failure email on final attempt
            if ($this->attempts() >= $this->tries) {
                if ($this->domain->client) {
                    SendEmailJob::dispatch(
                        $this->domain->client->email,
                        'Domain Renewal Failed',
                        'emails.domain-renewal-failed',
                        [
                            'domain' => $this->domain,
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }

            throw $e;
        }
    }

    protected function callRegistrarAPI($registrar): void
    {
        // Simulate API call
        sleep(1);
        
        Log::info("Registrar renewal API called (simulated)", [
            'registrar_id' => $registrar->id,
            'domain_name' => $this->domain->name,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Domain renewal job failed permanently", [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'error' => $exception->getMessage(),
        ]);
    }
}
