<?php

namespace App\Livewire\Partner;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{
    public array $metrics = [];
    public $recentActivities = [];

    public function mount()
    {
        $this->loadMetrics();
        $this->loadRecentActivities();
    }

    protected function loadMetrics()
    {
        $cacheKey = 'partner.dashboard.metrics.' . currentPartner()->id;
        
        $this->metrics = Cache::remember($cacheKey, 300, function () {
            $partnerId = currentPartner()->id;

            $totalRevenue = Invoice::where('partner_id', $partnerId)
                ->where('status', InvoiceStatus::Paid)
                ->sum('total');

            return [
                'total_clients' => User::whereClient()
                    ->where('partner_id', $partnerId)
                    ->count(),
                'total_domains' => Domain::where('partner_id', $partnerId)->count(),
                'active_domains' => Domain::where('partner_id', $partnerId)
                    ->where('status', DomainStatus::Active)
                    ->count(),
                'expiring_soon' => Domain::where('partner_id', $partnerId)
                    ->expiring(30)
                    ->count(),
                'total_revenue' => $totalRevenue,
                'wallet_balance' => partnerWallet()?->balance ?? 0,
            ];
        });
    }

    protected function loadRecentActivities()
    {
        $partnerId = currentPartner()->id;

        $this->recentActivities = AuditLog::where('partner_id', $partnerId)
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user_name' => $log->user?->name ?? 'System',
                    'auditable_type' => class_basename($log->auditable_type),
                    'created_at' => $log->created_at,
                ];
            });
    }

    public function refreshMetrics()
    {
        Cache::forget('partner.dashboard.metrics.' . currentPartner()->id);
        $this->loadMetrics();
        $this->loadRecentActivities();
        
        $this->dispatch('metrics-refreshed');
    }

    public function render()
    {
        return view('livewire.partner.dashboard')->layout('layouts.partner');
    }
}
