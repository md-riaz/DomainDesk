<?php

namespace App\Jobs;

use App\Mail\DomainTransferCompleted;
use App\Mail\DomainTransferFailed;
use App\Mail\DomainTransferInitiated;
use App\Models\Domain;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDomainTransferEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $domainId,
        public string $type,
        public string $email
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $domain = Domain::find($this->domainId);

        if (!$domain) {
            return;
        }

        $mailable = match ($this->type) {
            'initiated' => new DomainTransferInitiated($domain),
            'completed' => new DomainTransferCompleted($domain),
            'failed' => new DomainTransferFailed($domain),
            default => null,
        };

        if ($mailable) {
            Mail::to($this->email)->send($mailable);
        }
    }
}
