<?php

namespace App\Jobs;

use App\Mail\DomainRegistered;
use App\Models\Domain;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDomainRegistrationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Domain $domain;
    protected Invoice $invoice;

    /**
     * Create a new job instance.
     */
    public function __construct(Domain $domain, Invoice $invoice)
    {
        $this->domain = $domain;
        $this->invoice = $invoice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->domain->client->email)
            ->send(new DomainRegistered($this->domain, $this->invoice));
    }
}
