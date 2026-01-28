<?php

namespace App\Mail;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainExpiryWarning extends Mailable
{
    use Queueable, SerializesModels;

    public Domain $domain;
    public int $daysUntilExpiry;

    public function __construct(Domain $domain, int $daysUntilExpiry)
    {
        $this->domain = $domain;
        $this->daysUntilExpiry = $daysUntilExpiry;
    }

    public function envelope(): Envelope
    {
        $urgency = $this->daysUntilExpiry <= 7 ? 'URGENT: ' : '';
        return new Envelope(
            subject: $urgency . 'Domain Expiring Soon - ' . $this->domain->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.domain-expiry-warning',
            with: [
                'domainName' => $this->domain->name,
                'expiresAt' => $this->domain->expires_at->format('F j, Y'),
                'daysUntilExpiry' => $this->daysUntilExpiry,
                'autoRenew' => $this->domain->auto_renew,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
