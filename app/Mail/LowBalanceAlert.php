<?php

namespace App\Mail;

use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowBalanceAlert extends Mailable
{
    use Queueable, SerializesModels;

    public Partner $partner;
    public float $currentBalance;
    public float $threshold;
    public ?float $recommendedBalance;
    public ?float $estimatedUsage;
    public ?array $upcomingCharges;
    public ?int $activeDomains;
    public ?int $autoRenewEnabled;
    public ?array $nextRenewal;

    public function __construct(
        Partner $partner,
        float $currentBalance,
        float $threshold,
        ?float $recommendedBalance = null,
        ?float $estimatedUsage = null,
        ?array $upcomingCharges = null,
        ?int $activeDomains = null,
        ?int $autoRenewEnabled = null,
        ?array $nextRenewal = null
    ) {
        $this->partner = $partner;
        $this->currentBalance = $currentBalance;
        $this->threshold = $threshold;
        $this->recommendedBalance = $recommendedBalance;
        $this->estimatedUsage = $estimatedUsage;
        $this->upcomingCharges = $upcomingCharges;
        $this->activeDomains = $activeDomains;
        $this->autoRenewEnabled = $autoRenewEnabled;
        $this->nextRenewal = $nextRenewal;
    }

    public function envelope(): Envelope
    {
        $branding = $this->partner->branding;
        
        return new Envelope(
            subject: "⚠️ Low Account Balance - Action Required",
            from: $branding && $branding->email_sender_email 
                ? [$branding->email_sender_email => $branding->email_sender_name ?? config('app.name')]
                : null,
            replyTo: $branding && $branding->reply_to_email 
                ? [$branding->reply_to_email]
                : null,
        );
    }

    public function content(): Content
    {
        $branding = $this->partner->branding ?? (object)[
            'email_sender_name' => config('app.name'),
            'primary_color' => '#4f46e5',
            'secondary_color' => '#6366f1',
        ];
        
        return new Content(
            view: 'emails.low-balance-alert',
            with: [
                'branding' => $branding,
                'currentBalance' => $this->currentBalance,
                'threshold' => $this->threshold,
                'recommendedBalance' => $this->recommendedBalance,
                'estimatedUsage' => $this->estimatedUsage,
                'upcomingCharges' => $this->upcomingCharges,
                'activeDomains' => $this->activeDomains,
                'autoRenewEnabled' => $this->autoRenewEnabled,
                'nextRenewal' => $this->nextRenewal,
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
