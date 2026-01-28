<?php

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DomainExpiryAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Domain $domain,
        public int $daysUntilExpiry
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->getUrgencyLevel();
        
        return (new MailMessage)
            ->subject("⚠️ Domain Expiring Soon: {$this->domain->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your domain **{$this->domain->name}** will expire in **{$this->daysUntilExpiry} day(s)**.")
            ->line("Expiry Date: {$this->domain->expires_at->format('F j, Y')}")
            ->when($urgency === 'critical', function ($mail) {
                return $mail->line('⚠️ **URGENT**: This domain is about to expire! Please take immediate action.');
            })
            ->action('Renew Domain', url("/domains/{$this->domain->id}/renew"))
            ->line('To avoid service interruption, please renew your domain before it expires.')
            ->when($this->domain->auto_renew, function ($mail) {
                return $mail->line('✓ Auto-renewal is enabled for this domain.');
            })
            ->line('Thank you for using our service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->domain->expires_at->toDateTimeString(),
            'urgency' => $this->getUrgencyLevel(),
            'auto_renew' => $this->domain->auto_renew,
            'action_url' => url("/domains/{$this->domain->id}/renew"),
        ];
    }

    protected function getUrgencyLevel(): string
    {
        return match (true) {
            $this->daysUntilExpiry <= 1 => 'critical',
            $this->daysUntilExpiry <= 7 => 'high',
            $this->daysUntilExpiry <= 15 => 'medium',
            default => 'low',
        };
    }
}
