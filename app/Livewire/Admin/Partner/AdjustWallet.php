<?php

namespace App\Livewire\Admin\Partner;

use App\Models\Partner;
use App\Services\PartnerOnboardingService;
use Livewire\Component;

class AdjustWallet extends Component
{
    public $partnerId;
    public $partner;
    public $type = 'credit';
    public $amount = 0;
    public $reason = '';

    public function mount($partnerId = null)
    {
        if ($partnerId) {
            $this->partnerId = $partnerId;
            $this->partner = Partner::withoutGlobalScopes()->findOrFail($partnerId);
        }
    }

    protected function rules()
    {
        return [
            'partnerId' => ['required', 'exists:partners,id'],
            'type' => ['required', 'in:credit,debit,adjustment'],
            'amount' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($this->type === 'adjustment') {
                        // For adjustments, allow any numeric value including negative
                        if (!is_numeric($value)) {
                            $fail('The amount must be a number.');
                        }
                    } else {
                        // For credit/debit, require positive values
                        if ($value <= 0) {
                            $fail('The amount must be greater than 0.');
                        }
                    }
                },
            ],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function render()
    {
        $partners = Partner::withoutGlobalScopes()
            ->orderBy('name')
            ->get();

        return view('livewire.admin.partner.adjust-wallet', [
            'partners' => $partners,
        ]);
    }

    public function adjustBalance()
    {
        $this->validate();

        try {
            $partner = Partner::withoutGlobalScopes()->findOrFail($this->partnerId);
            
            $service = new PartnerOnboardingService();
            $service->adjustWalletBalance(
                $partner,
                $this->amount,
                $this->reason,
                $this->type
            );

            $this->dispatch('wallet-adjusted');
            $this->dispatch('close-modal');
            
            session()->flash('success', 'Wallet balance adjusted successfully');
            
            // Reset form
            $this->reset(['type', 'amount', 'reason']);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to adjust wallet: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        $this->dispatch('close-modal');
    }
}
