<?php

namespace App\Livewire\Admin;

use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $metrics = Cache::remember('admin_dashboard_metrics', 600, function () {
            return $this->calculateMetrics();
        });

        $recentTransactions = $this->getRecentTransactions();
        $recentActivity = $this->getRecentActivity();
        $systemHealth = $this->getSystemHealth();

        return view('livewire.admin.dashboard', [
            'metrics' => $metrics,
            'recentTransactions' => $recentTransactions,
            'recentActivity' => $recentActivity,
            'systemHealth' => $systemHealth,
        ])->layout('layouts.admin', ['title' => 'Admin Dashboard']);
    }

    private function calculateMetrics(): array
    {
        // Partner metrics
        $totalPartners = Partner::withoutGlobalScopes()->count();
        $activePartners = Partner::withoutGlobalScopes()->where('status', 'active')->count();
        $suspendedPartners = Partner::withoutGlobalScopes()->where('status', 'suspended')->count();

        // Client metrics
        $totalClients = User::withoutGlobalScopes()->whereClient()->count();

        // Domain metrics
        $totalDomains = Domain::withoutGlobalScopes()->count();
        $activeDomains = Domain::withoutGlobalScopes()->where('status', 'active')->count();
        $pendingDomains = Domain::withoutGlobalScopes()->where('status', 'pending')->count();
        $expiredDomains = Domain::withoutGlobalScopes()->where('status', 'expired')->count();

        // Revenue metrics
        $totalRevenue = Invoice::withoutGlobalScopes()
            ->where('status', 'paid')
            ->sum('total');

        $revenueThisMonth = Invoice::withoutGlobalScopes()
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        $revenueLastMonth = Invoice::withoutGlobalScopes()
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->sum('total');

        // Calculate revenue trend
        $revenueTrend = 0;
        if ($revenueLastMonth > 0) {
            $revenueTrend = (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100;
        }

        // Wallet metrics
        $totalWalletBalance = DB::table('wallet_transactions')
            ->selectRaw('
                SUM(CASE 
                    WHEN type IN ("credit", "refund") THEN amount
                    WHEN type = "debit" THEN -amount
                    WHEN type = "adjustment" THEN amount
                    ELSE 0
                END) as total_balance
            ')
            ->value('total_balance') ?? 0;

        // Registrar count
        $activeRegistrars = Registrar::where('is_active', true)->count();

        return [
            'partners' => [
                'total' => $totalPartners,
                'active' => $activePartners,
                'suspended' => $suspendedPartners,
            ],
            'clients' => $totalClients,
            'domains' => [
                'total' => $totalDomains,
                'active' => $activeDomains,
                'pending' => $pendingDomains,
                'expired' => $expiredDomains,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'this_month' => $revenueThisMonth,
                'trend' => $revenueTrend,
            ],
            'wallet_balance' => $totalWalletBalance,
            'registrars' => $activeRegistrars,
        ];
    }

    private function getRecentTransactions()
    {
        return WalletTransaction::withoutGlobalScopes()
            ->with('partner')
            ->latest()
            ->limit(10)
            ->get();
    }

    private function getRecentActivity(): array
    {
        $activity = [];

        // Recent partner registrations
        $newPartners = Partner::withoutGlobalScopes()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'type' => 'partner_registered',
                'message' => "New partner registered: {$p->name}",
                'timestamp' => $p->created_at,
                'link' => route('admin.partners.show', $p->id),
            ]);

        $activity = array_merge($activity, $newPartners->toArray());

        // Recent domain registrations
        $newDomains = Domain::withoutGlobalScopes()
            ->with('partner')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($d) => [
                'type' => 'domain_registered',
                'message' => "New domain: {$d->domain_name} ({$d->partner->name})",
                'timestamp' => $d->created_at,
                'link' => null,
            ]);

        $activity = array_merge($activity, $newDomains->toArray());

        // Large transactions (> $500)
        $largeTransactions = WalletTransaction::withoutGlobalScopes()
            ->with('partner')
            ->where('amount', '>', 500)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'type' => 'large_transaction',
                'message' => "Large {$t->type}: $" . number_format($t->amount, 2) . " ({$t->partner->name})",
                'timestamp' => $t->created_at,
                'link' => null,
            ]);

        $activity = array_merge($activity, $largeTransactions->toArray());

        // Sort by timestamp descending
        usort($activity, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($activity, 0, 15);
    }

    private function getSystemHealth(): array
    {
        $health = [];

        // Database check
        try {
            DB::connection()->getPdo();
            $health['database'] = ['status' => 'healthy', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $health['database'] = ['status' => 'unhealthy', 'message' => 'Connection failed'];
        }

        // Cache check
        try {
            Cache::put('health_check', true, 1);
            $health['cache'] = ['status' => 'healthy', 'message' => 'Working'];
        } catch (\Exception $e) {
            $health['cache'] = ['status' => 'unhealthy', 'message' => 'Failed'];
        }

        // Storage check
        $storageUsed = $this->getStorageUsage();
        $health['storage'] = [
            'status' => $storageUsed < 80 ? 'healthy' : 'warning',
            'message' => number_format($storageUsed, 1) . '% used'
        ];

        // Queue check (simplified - check if jobs table exists)
        try {
            $pendingJobs = DB::table('jobs')->count();
            $health['queue'] = [
                'status' => $pendingJobs < 100 ? 'healthy' : 'warning',
                'message' => $pendingJobs . ' pending jobs'
            ];
        } catch (\Exception $e) {
            $health['queue'] = ['status' => 'unknown', 'message' => 'Not configured'];
        }

        return $health;
    }

    private function getStorageUsage(): float
    {
        $path = storage_path();
        
        try {
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            
            if ($total && $free) {
                return (($total - $free) / $total) * 100;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 0;
    }

    public function refreshMetrics()
    {
        Cache::forget('admin_dashboard_metrics');
        $this->dispatch('metrics-refreshed');
    }
}
