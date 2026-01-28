<?php

namespace App\Mail;

use App\Models\Domain;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainExpiryAlert extends Mailable
{
    use Queueable, SerializesModels;

    public Domain $domain;
    public int $daysExpired;
    public float $renewalCost;
    public float $redemptionFee;
    public Carbon $gracePeriodEnds;
    public int $daysUntilGracePeriodEnds;

    public function __construct(Domain $domain, int $daysExpired, float $renewalCost, float $redemptionFee = 0, int $gracePeriodDays = 30)
    {
        $this->domain = $domain;
        $this->daysExpired = $daysExpired;
        $this->renewalCost = $renewalCost;
        $this->redemptionFee = $redemptionFee;
        $this->gracePeriodEnds = $domain->expires_at->copy()->addDays($gracePeriodDays);
        $this->daysUntilGracePeriodEnds = max(0, now()->diffInDays($this->gracePeriodEnds, false));
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
            subject: "🚨 CRITICAL: {$this->domain->name} has EXPIRED!",
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
            view: 'emails.domain-expiry-alert',
            with: [
                'domain' => $this->domain,
                'branding' => $branding,
                'daysExpired' => $this->daysExpired,
                'renewalCost' => $this->renewalCost,
                'redemptionFee' => $this->redemptionFee,
                'gracePeriodEnds' => $this->gracePeriodEnds,
                'daysUntilGracePeriodEnds' => $this->daysUntilGracePeriodEnds,
                'renewalUrl' => url('/dashboard/domains/' . $this->domain->id . '/renew'),
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
