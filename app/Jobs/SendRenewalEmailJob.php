<?php

namespace App\Jobs;

use App\Mail\DomainExpiryWarning;
use App\Mail\DomainRenewed;
use App\Mail\DomainRenewalFailed;
use App\Models\Domain;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRenewalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Domain $domain;
    protected ?Invoice $invoice;
    protected string $type;
    protected ?string $reason;

    /**
     * Create a new job instance.
     *
     * @param Domain $domain
     * @param Invoice|null $invoice
     * @param string $type Type of email: 'success', 'failed', 'auto_renew_failed', 'expiry_warning'
     * @param string|null $reason Failure reason (for failed emails)
     */
    public function __construct(Domain $domain, ?Invoice $invoice, string $type = 'success', ?string $reason = null)
    {
        $this->domain = $domain;
        $this->invoice = $invoice;
        $this->type = $type;
        $this->reason = $reason;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $clientEmail = $this->domain->client->email;
        $partnerEmail = $this->domain->partner->email ?? null;

        switch ($this->type) {
            case 'success':
                if ($this->invoice) {
                    Mail::to($clientEmail)
                        ->send(new DomainRenewed($this->domain, $this->invoice));
                }
                break;

            case 'failed':
                Mail::to($clientEmail)
                    ->send(new DomainRenewalFailed($this->domain, $this->reason ?? 'Unknown error', false));
                break;

            case 'auto_renew_failed':
                // Send to both client and partner for auto-renewal failures
                Mail::to($clientEmail)
                    ->send(new DomainRenewalFailed($this->domain, $this->reason ?? 'Unknown error', true));
                
                if ($partnerEmail) {
                    Mail::to($partnerEmail)
                        ->send(new DomainRenewalFailed($this->domain, $this->reason ?? 'Unknown error', true));
                }
                break;

            case 'expiry_warning':
                $daysUntilExpiry = $this->domain->daysUntilExpiry() ?? 0;
                Mail::to($clientEmail)
                    ->send(new DomainExpiryWarning($this->domain, $daysUntilExpiry));
                break;
        }
    }
}
