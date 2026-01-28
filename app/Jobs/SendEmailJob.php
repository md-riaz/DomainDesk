<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        public string $to,
        public string $subject,
        public string $view,
        public array $data = [],
        public ?string $from = null
    ) {}

    public function handle(): void
    {
        // Rate limiting: max 10 emails per minute per recipient
        $executed = RateLimiter::attempt(
            "send-email:{$this->to}",
            $perMinute = 10,
            function() {
                $this->sendEmail();
            }
        );

        if (!$executed) {
            Log::warning("Email rate limit exceeded", [
                'to' => $this->to,
                'subject' => $this->subject,
            ]);
            
            // Release back to queue after 60 seconds
            $this->release(60);
        }
    }

    protected function sendEmail(): void
    {
        try {
            Mail::send($this->view, $this->data, function ($message) {
                $message->to($this->to)
                    ->subject($this->subject);
                
                if ($this->from) {
                    $message->from($this->from);
                }
            });

            Log::info("Email sent successfully", [
                'to' => $this->to,
                'subject' => $this->subject,
                'attempt' => $this->attempts(),
            ]);

        } catch (\Exception $e) {
            Log::error("Email sending failed", [
                'to' => $this->to,
                'subject' => $this->subject,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Email job failed permanently", [
            'to' => $this->to,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
