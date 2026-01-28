<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Notifications\RenewalReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendRenewalReminders extends Command
{
    protected $signature = 'domains:send-renewal-reminders';

    protected $description = 'Send renewal reminder notifications to domain owners';

    public function handle(): int
    {
        $this->info('Sending renewal reminders...');

        $reminderIntervals = [30, 15, 7, 1];
        $totalSent = 0;

        foreach ($reminderIntervals as $days) {
            $domains = Domain::active()
                ->where('expires_at', '>=', now()->addDays($days)->startOfDay())
                ->where('expires_at', '<=', now()->addDays($days)->endOfDay())
                ->with(['client', 'partner'])
                ->get();

            foreach ($domains as $domain) {
                if (!$domain->client) {
                    continue;
                }

                // Check if reminder already sent for this interval
                $alreadySent = DB::table('notifications')
                    ->where('type', RenewalReminder::class)
                    ->where('notifiable_id', $domain->client_id)
                    ->where('notifiable_type', get_class($domain->client))
                    ->whereRaw("JSON_EXTRACT(data, '$.domain_id') = ?", [$domain->id])
                    ->whereRaw("JSON_EXTRACT(data, '$.days_until_expiry') = ?", [$days])
                    ->exists();

                if ($alreadySent) {
                    $this->line("Skipping {$domain->name}: Reminder already sent");
                    continue;
                }

                $domain->client->notify(new RenewalReminder($domain, $days));
                $totalSent++;
                
                $this->line("✓ Sent reminder for {$domain->name} ({$days} days)");
                Log::info("Renewal reminder sent", [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'client_id' => $domain->client_id,
                    'days_until_expiry' => $days,
                ]);
            }

            $this->line("Processed {$domains->count()} domain(s) expiring in {$days} days");
        }

        $this->newLine();
        $this->info("✓ Sent {$totalSent} renewal reminder(s)");
        
        Log::info("SendRenewalReminders completed", ['total_sent' => $totalSent]);

        return self::SUCCESS;
    }
}
