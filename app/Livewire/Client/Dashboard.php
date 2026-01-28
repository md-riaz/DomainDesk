<?php

namespace App\Livewire\Client;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Models\Domain;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{
    public array $metrics = [];
    public $recentDomains = [];
    public $recentInvoices = [];

    public function mount()
    {
        $this->loadMetrics();
        $this->loadRecentData();
    }

    protected function loadMetrics()
    {
        $cacheKey = 'dashboard.metrics.' . auth()->id();
        
        $this->metrics = Cache::remember($cacheKey, 300, function () {
            $userId = auth()->id();
            $partnerId = currentPartner()->id;

            return [
                'total_domains' => Domain::where('client_id', $userId)
                    ->where('partner_id', $partnerId)
                    ->count(),
                'active_domains' => Domain::where('client_id', $userId)
                    ->where('partner_id', $partnerId)
                    ->where('status', DomainStatus::Active)
                    ->count(),
                'expiring_soon' => Domain::where('client_id', $userId)
                    ->where('partner_id', $partnerId)
                    ->expiring(30)
                    ->count(),
                'pending_renewals' => Domain::where('client_id', $userId)
                    ->where('partner_id', $partnerId)
                    ->where('auto_renew', false)
                    ->expiring(30)
                    ->count(),
            ];
        });
    }

    protected function loadRecentData()
    {
        $userId = auth()->id();
        $partnerId = currentPartner()->id;

        $this->recentDomains = Domain::where('client_id', $userId)
            ->where('partner_id', $partnerId)
            ->latest()
            ->limit(5)
            ->get();

        $this->recentInvoices = Invoice::where('client_id', $userId)
            ->where('partner_id', $partnerId)
            ->with('items')
            ->latest()
            ->limit(5)
            ->get();
    }

    public function refreshMetrics()
    {
        Cache::forget('dashboard.metrics.' . auth()->id());
        $this->loadMetrics();
        $this->loadRecentData();
    }

    public function render()
    {
        return view('livewire.client.dashboard')->layout('layouts.client');
    }
}
