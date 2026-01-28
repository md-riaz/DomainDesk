<?php

namespace App\Mail;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainRenewalFailed extends Mailable
{
    use Queueable, SerializesModels;

    public Domain $domain;
    public string $reason;
    public bool $isAutoRenewal;

    public function __construct(Domain $domain, string $reason, bool $isAutoRenewal = false)
    {
        $this->domain = $domain;
        $this->reason = $reason;
        $this->isAutoRenewal = $isAutoRenewal;
    }

    public function envelope(): Envelope
    {
        $subject = $this->isAutoRenewal 
            ? 'Auto-Renewal Failed - ' . $this->domain->name
            : 'Domain Renewal Failed - ' . $this->domain->name;

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.domain-renewal-failed',
            with: [
                'domainName' => $this->domain->name,
                'expiresAt' => $this->domain->expires_at->format('F j, Y'),
                'daysUntilExpiry' => $this->domain->daysUntilExpiry(),
                'reason' => $this->reason,
                'isAutoRenewal' => $this->isAutoRenewal,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
