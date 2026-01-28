<?php

namespace App\Livewire\Client\Domain;

use App\Models\Domain;
use App\Services\DomainRenewalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RenewDomain extends Component
{
    public Domain $domain;
    public int $years = 1;
    public ?string $price = null;
    public ?array $renewabilityCheck = null;
    public bool $isProcessing = false;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    protected DomainRenewalService $renewalService;

    public function boot(DomainRenewalService $renewalService): void
    {
        $this->renewalService = $renewalService;
    }

    public function mount(Domain $domain): void
    {
        // Verify domain belongs to current user
        if ($domain->client_id !== Auth::id()) {
            abort(403, 'Unauthorized access to domain');
        }

        $this->domain = $domain;
        $this->checkRenewability();
        $this->calculatePrice();
    }

    public function updatedYears(): void
    {
        $this->errorMessage = null;
        $this->calculatePrice();
    }

    public function checkRenewability(): void
    {
        $this->renewabilityCheck = $this->renewalService->checkRenewability($this->domain);
    }

    public function calculatePrice(): void
    {
        $this->price = $this->renewalService->calculateRenewalPrice($this->domain, $this->years);
    }

    public function renewDomain(): void
    {
        $this->isProcessing = true;
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            // Validate years
            if ($this->years < 1 || $this->years > 10) {
                throw new \Exception('Renewal period must be between 1 and 10 years');
            }

            // Check renewability again before processing
            $this->checkRenewability();
            if (!$this->renewabilityCheck['renewable']) {
                throw new \Exception($this->renewabilityCheck['reason']);
            }

            // Process renewal
            $result = $this->renewalService->renewDomain(
                $this->domain,
                $this->years,
                Auth::id()
            );

            if ($result['success']) {
                $this->successMessage = $result['message'];
                
                // Redirect to domain details after 2 seconds
                $this->dispatch('renewal-success', [
                    'message' => $result['message'],
                    'domain_id' => $this->domain->id,
                ]);
                
                // Refresh domain data
                $this->domain = $result['domain'];
                
                // Redirect after showing success message
                $this->redirect(route('client.domains.show', $this->domain), navigate: true);
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function getWalletBalanceProperty(): float
    {
        $wallet = \App\Models\Wallet::where('partner_id', Auth::user()->partner_id)->first();
        return $wallet ? $wallet->balance : 0.0;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.client.domain.renew-domain', [
            'walletBalance' => $this->walletBalance,
        ]);
    }
}
