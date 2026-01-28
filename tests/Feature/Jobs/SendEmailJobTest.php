<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendEmailJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SendEmailJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('send-email:test@example.com');
    }

    public function test_sends_email_successfully(): void
    {
        $job = new SendEmailJob(
            'test@example.com',
            'Test Subject',
            'emails.test',
            ['key' => 'value']
        );

        $job->handle();

        Mail::assertSent(function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_respects_rate_limiting(): void
    {
        // Send 10 emails (the limit)
        for ($i = 0; $i < 10; $i++) {
            $job = new SendEmailJob(
                'test@example.com',
                "Test Subject $i",
                'emails.test',
                []
            );
            $job->handle();
        }

        // 11th email should be rate limited
        $job = new SendEmailJob(
            'test@example.com',
            'Test Subject 11',
            'emails.test',
            []
        );

        $job->handle();

        // Should have sent exactly 10 emails
        Mail::assertSentCount(10);
    }

    public function test_uses_custom_from_address(): void
    {
        $job = new SendEmailJob(
            'test@example.com',
            'Test Subject',
            'emails.test',
            [],
            'custom@example.com'
        );

        $job->handle();

        Mail::assertSent(function ($mail) {
            return $mail->hasFrom('custom@example.com');
        });
    }

    public function test_job_has_correct_configuration(): void
    {
        $job = new SendEmailJob(
            'test@example.com',
            'Test Subject',
            'emails.test'
        );

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(30, $job->timeout);
    }

    public function test_retry_until_is_24_hours(): void
    {
        $job = new SendEmailJob(
            'test@example.com',
            'Test Subject',
            'emails.test'
        );

        $retryUntil = $job->retryUntil();
        $expected = now()->addHours(24);

        $this->assertEquals($expected->format('Y-m-d H:i'), $retryUntil->format('Y-m-d H:i'));
    }

    public function test_handles_email_sending_failure(): void
    {
        Mail::shouldReceive('send')->andThrow(new \Exception('SMTP error'));

        $job = new SendEmailJob(
            'test@example.com',
            'Test Subject',
            'emails.test'
        );

        $this->expectException(\Exception::class);
        $job->handle();
    }
}
