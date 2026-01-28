<?php

namespace App\Livewire\Client\Domain;

use App\Enums\PriceAction;
use App\Models\Domain;
use App\Models\Tld;
use App\Services\DomainTransferService;
use App\Services\PricingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Transfer Domain')]
class TransferDomain extends Component
{
    public string $domainName = '';
    public string $authCode = '';
    public bool $autoRenew = false;
    public bool $showAuthCode = false;
    public ?float $transferFee = null;
    public ?string $errorMessage = null;
    public bool $isProcessing = false;
    public ?int $transferredDomainId = null;

    protected DomainTransferService $transferService;
    protected PricingService $pricingService;

    public function boot(
        DomainTransferService $transferService,
        PricingService $pricingService
    ): void {
        $this->transferService = $transferService;
        $this->pricingService = $pricingService;
    }

    public function rules(): array
    {
        return [
            'domainName' => 'required|string|regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/i',
            'authCode' => 'required|string|min:6',
            'autoRenew' => 'boolean',
        ];
    }

    public function mount(): void
    {
        $this->domainName = request()->query('domain', '');
        if ($this->domainName) {
            $this->calculateTransferFee();
        }
    }

    public function updatedDomainName(): void
    {
        $this->errorMessage = null;
        $this->transferFee = null;
        
        if ($this->domainName) {
            $this->calculateTransferFee();
        }
    }

    public function calculateTransferFee(): void
    {
        try {
            $this->validate(['domainName' => $this->rules()['domainName']]);

            $domainName = strtolower(trim($this->domainName));
            $parts = explode('.', $domainName);
            
            if (count($parts) < 2) {
                $this->transferFee = null;
                return;
            }

            $tldName = end($parts);
            $tld = Tld::where('extension', $tldName)->where('is_active', true)->first();

            if (!$tld) {
                $this->errorMessage = 'TLD not supported';
                $this->transferFee = null;
                return;
            }

            $user = Auth::user();
            $this->transferFee = (float) $this->pricingService->calculateFinalPrice(
                $tld,
                $user->partner,
                PriceAction::TRANSFER,
                1
            );
        } catch (\Exception $e) {
            $this->transferFee = null;
        }
    }

    public function toggleAuthCodeVisibility(): void
    {
        $this->showAuthCode = !$this->showAuthCode;
    }

    public function transfer(): void
    {
        $this->validate();
        $this->errorMessage = null;
        $this->isProcessing = true;

        try {
            $user = Auth::user();
            
            $result = $this->transferService->initiateTransferIn([
                'domain_name' => strtolower(trim($this->domainName)),
                'auth_code' => $this->authCode,
                'client_id' => $user->id,
                'partner_id' => $user->partner_id,
                'auto_renew' => $this->autoRenew,
            ]);

            if ($result['success']) {
                $this->transferredDomainId = $result['domain']->id;
                session()->flash('success', $result['message']);
                $this->redirect(route('client.domains.transfer-status', ['domain' => $result['domain']->id]));
            } else {
                $this->errorMessage = $result['message'];
                $this->isProcessing = false;
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Transfer failed: ' . $e->getMessage();
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        $user = Auth::user();
        $walletBalance = $user->partner->wallet->balance ?? 0;

        return view('livewire.client.domain.transfer-domain', [
            'walletBalance' => $walletBalance,
        ]);
    }
}
