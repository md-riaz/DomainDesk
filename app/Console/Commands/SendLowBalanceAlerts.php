<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\Setting;
use App\Notifications\LowBalanceAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLowBalanceAlerts extends Command
{
    protected $signature = 'partners:send-low-balance-alerts';

    protected $description = 'Send low balance alerts to partner administrators';

    public function handle(): int
    {
        $this->info('Checking partner wallet balances...');

        $lowBalanceThreshold = Setting::get('low_balance_threshold', 100.00);
        
        $partners = Partner::active()
            ->with(['wallet', 'users'])
            ->get();

        $alertsSent = 0;

        foreach ($partners as $partner) {
            if (!$partner->wallet) {
                $this->warn("Partner {$partner->name} has no wallet");
                continue;
            }

            $balance = $partner->wallet->balance;

            if ($balance < $lowBalanceThreshold) {
                // Get partner admin users
                $adminUsers = $partner->users()->whereIn('role', ['partner', 'super_admin'])->get();

                if ($adminUsers->isEmpty()) {
                    $this->warn("No admin users found for partner {$partner->name}");
                    Log::warning("Low balance alert skipped: No admin users", [
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                        'balance' => $balance,
                    ]);
                    continue;
                }

                foreach ($adminUsers as $user) {
                    $user->notify(new LowBalanceAlert($partner, $balance, $lowBalanceThreshold));
                    $alertsSent++;
                }

                $this->line("✓ Sent low balance alert for {$partner->name} (Balance: \${$balance})");
                Log::info("Low balance alert sent", [
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'balance' => $balance,
                    'threshold' => $lowBalanceThreshold,
                    'recipients' => $adminUsers->count(),
                ]);
            }
        }

        $this->newLine();
        $this->info("✓ Sent {$alertsSent} low balance alert(s)");
        
        Log::info("SendLowBalanceAlerts completed", [
            'alerts_sent' => $alertsSent,
            'threshold' => $lowBalanceThreshold,
        ]);

        return self::SUCCESS;
    }
}
