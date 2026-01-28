<?php

namespace App\Mail;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainTransferInitiated extends Mailable
{
    use Queueable, SerializesModels;

    public Domain $domain;
    public ?string $status;
    public ?string $expectedCompletion;
    public ?string $currentRegistrar;
    public ?string $authCode;

    public function __construct(
        Domain $domain,
        ?string $status = null,
        ?string $expectedCompletion = null,
        ?string $currentRegistrar = null,
        ?string $authCode = null
    ) {
        $this->domain = $domain;
        $this->status = $status ?? 'Pending';
        $this->expectedCompletion = $expectedCompletion;
        $this->currentRegistrar = $currentRegistrar;
        $this->authCode = $authCode;
    }

    public function envelope(): Envelope
    {
        $branding = $this->domain->partner->branding;
        
        $from = $branding && $branding->email_sender_email 
            ? new Address($branding->email_sender_email, $branding->email_sender_name ?? config('app.name'))
            : null;
            
        $replyTo = $branding && $branding->reply_to_email 
            ? [new Address($branding->reply_to_email)]
            : [];
        
        return new Envelope(
            subject: 'Domain Transfer Initiated - ' . $this->domain->name,
            from: $from,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        $branding = $this->domain->partner->branding ?? (object)[
            'email_sender_name' => config('app.name'),
            'primary_color' => '#4f46e5',
            'secondary_color' => '#6366f1',
        ];
        
        return new Content(
            view: 'emails.domain-transfer-initiated',
            with: [
                'domain' => $this->domain,
                'branding' => $branding,
                'status' => $this->status,
                'expectedCompletion' => $this->expectedCompletion,
                'currentRegistrar' => $this->currentRegistrar,
                'authCode' => $this->authCode,
                'dashboardUrl' => url('/dashboard'),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
