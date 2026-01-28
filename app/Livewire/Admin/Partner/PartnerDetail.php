<?php

namespace App\Livewire\Admin\Partner;

use App\Models\Partner;
use App\Services\PartnerOnboardingService;
use Livewire\Component;

class PartnerDetail extends Component
{
    public Partner $partner;
    public $showAdjustWallet = false;

    public function mount($partnerId)
    {
        $this->partner = Partner::withoutGlobalScopes()
            ->with(['wallet', 'branding', 'users', 'clientDomains', 'invoices'])
            ->withCount(['users' => function ($query) {
                $query->whereClient();
            }])
            ->withCount('clientDomains')
            ->withCount('invoices')
            ->findOrFail($partnerId);
    }

    public function render()
    {
        $recentTransactions = $this->partner->wallet
            ? $this->partner->wallet->transactions()->latest()->limit(20)->get()
            : collect();

        $recentInvoices = $this->partner->invoices()
            ->latest()
            ->limit(10)
            ->get();

        $totalRevenue = $this->partner->invoices()
            ->where('status', 'paid')
            ->sum('total');

        return view('livewire.admin.partner.partner-detail', [
            'recentTransactions' => $recentTransactions,
            'recentInvoices' => $recentInvoices,
            'totalRevenue' => $totalRevenue,
        ])->layout('layouts.admin', [
            'title' => $this->partner->name,
            'breadcrumbs' => [
                ['label' => 'Partners', 'url' => route('admin.partners.list')],
                ['label' => $this->partner->name],
            ],
        ]);
    }

    public function suspendPartner()
    {
        $service = new PartnerOnboardingService();
        $service->suspendPartner($this->partner, 'Suspended via admin panel');
        
        $this->partner->refresh();
        session()->flash('success', 'Partner suspended successfully');
    }

    public function activatePartner()
    {
        $service = new PartnerOnboardingService();
        $service->activatePartner($this->partner);
        
        $this->partner->refresh();
        session()->flash('success', 'Partner activated successfully');
    }

    public function impersonatePartner()
    {
        // Double-check authorization
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        
        session(['impersonating_partner_id' => $this->partner->id]);
        
        auditLog('Started impersonating partner', $this->partner);

        return redirect()->route('partner.dashboard');
    }

    public function openAdjustWallet()
    {
        $this->showAdjustWallet = true;
    }

    public function closeAdjustWallet()
    {
        $this->showAdjustWallet = false;
    }
}
