<?php

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalReminder extends Notification implements ShouldQueue
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
        $message = $this->getUrgencyMessage();
        
        $mail = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting("Hello {$notifiable->name}!");

        // Add urgency-specific content
        if ($urgency === 'critical') {
            $mail->error()
                ->line('🚨 **CRITICAL REMINDER**')
                ->line($message);
        } elseif ($urgency === 'high') {
            $mail->line('⚠️ **IMPORTANT REMINDER**')
                ->line($message);
        } else {
            $mail->line($message);
        }

        $mail->line("**Domain:** {$this->domain->name}")
            ->line("**Expires On:** {$this->domain->expires_at->format('F j, Y h:i A')}")
            ->line("**Days Remaining:** {$this->daysUntilExpiry}");

        // Add auto-renewal info if enabled
        if ($this->domain->auto_renew) {
            $mail->line('✓ Auto-renewal is **enabled** for this domain. We will attempt to renew it automatically.');
        } else {
            $mail->line('⚠️ Auto-renewal is **not enabled**. Please renew manually to avoid service disruption.');
            $mail->action('Renew Now', url("/domains/{$this->domain->id}/renew"));
        }

        // Add alternative actions
        $mail->line('You can also:')
            ->line('• View domain details: ' . url("/domains/{$this->domain->id}"))
            ->line('• Enable auto-renewal: ' . url("/domains/{$this->domain->id}/settings"))
            ->line('Thank you for using our service!');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->domain->expires_at->toDateTimeString(),
            'urgency' => $this->getUrgencyLevel(),
            'message' => $this->getUrgencyMessage(),
            'auto_renew' => $this->domain->auto_renew,
            'renew_url' => url("/domains/{$this->domain->id}/renew"),
            'details_url' => url("/domains/{$this->domain->id}"),
        ];
    }

    protected function getSubject(): string
    {
        return match ($this->getUrgencyLevel()) {
            'critical' => "🚨 URGENT: {$this->domain->name} expires in {$this->daysUntilExpiry} day(s)!",
            'high' => "⚠️ Important: {$this->domain->name} expires soon",
            'medium' => "Reminder: {$this->domain->name} renewal due",
            default => "Domain renewal reminder: {$this->domain->name}",
        };
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

    protected function getUrgencyMessage(): string
    {
        return match ($this->getUrgencyLevel()) {
            'critical' => "Your domain expires in just {$this->daysUntilExpiry} day(s)! Take immediate action to avoid losing your domain.",
            'high' => "Your domain will expire in {$this->daysUntilExpiry} days. Please renew soon to ensure continuity.",
            'medium' => "This is a friendly reminder that your domain will expire in {$this->daysUntilExpiry} days.",
            default => "Your domain renewal is due in {$this->daysUntilExpiry} days. Consider renewing now for peace of mind.",
        };
    }
}
