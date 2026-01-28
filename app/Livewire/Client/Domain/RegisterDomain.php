<?php

namespace App\Livewire\Client\Domain;

use App\Enums\ContactType;
use App\Jobs\SendDomainRegistrationEmail;
use App\Models\Tld;
use App\Services\DomainRegistrationService;
use App\Services\PricingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RegisterDomain extends Component
{
    // Step tracking
    public int $currentStep = 1;
    public int $totalSteps = 5;

    // Step 1: Domain selection
    public string $domainName = '';
    public ?Tld $tld = null;

    // Step 2: Registration period
    public int $years = 1;
    public bool $autoRenew = false;
    public ?string $price = null;

    // Step 3: Contact information
    public bool $useDefaultContacts = true;
    public array $registrantContact = [];
    public bool $useSameForAll = true;

    // Step 4: Nameservers
    public bool $useDefaultNameservers = true;
    public array $nameservers = ['', ''];

    // Step 5: Review
    public bool $acceptTerms = false;

    // UI state
    public bool $isProcessing = false;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    protected DomainRegistrationService $registrationService;
    protected PricingService $pricingService;

    public function boot(
        DomainRegistrationService $registrationService,
        PricingService $pricingService
    ): void {
        $this->registrationService = $registrationService;
        $this->pricingService = $pricingService;
    }

    public function mount(?string $domain = null): void
    {
        if ($domain) {
            $this->domainName = strtolower(trim($domain));
            $this->extractTld();
        }

        $this->initializeContactForm();
    }

    protected function initializeContactForm(): void
    {
        $user = Auth::user();
        $nameParts = explode(' ', $user->name);

        $this->registrantContact = [
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? 'User',
            'email' => $user->email,
            'phone' => '',
            'organization' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'country' => 'US',
        ];
    }

    public function nextStep(): void
    {
        $this->errorMessage = null;

        // Validate current step before proceeding
        if ($this->currentStep === 1) {
            $this->validateStep1();
        } elseif ($this->currentStep === 2) {
            $this->validateStep2();
        } elseif ($this->currentStep === 3) {
            $this->validateStep3();
        } elseif ($this->currentStep === 4) {
            $this->validateStep4();
        }

        if (!$this->errorMessage) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        $this->errorMessage = null;
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step <= $this->currentStep || $step === 1) {
            $this->currentStep = $step;
            $this->errorMessage = null;
        }
    }

    protected function validateStep1(): void
    {
        if (empty($this->domainName)) {
            $this->errorMessage = 'Please enter a domain name.';
            return;
        }

        if (!$this->extractTld()) {
            $this->errorMessage = 'Invalid domain name or TLD not supported.';
            return;
        }
    }

    protected function validateStep2(): void
    {
        if ($this->years < 1 || $this->years > 10) {
            $this->errorMessage = 'Please select a registration period between 1 and 10 years.';
            return;
        }

        $this->calculatePrice();
    }

    protected function validateStep3(): void
    {
        if (!$this->useDefaultContacts) {
            $required = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'postal_code', 'country'];
            foreach ($required as $field) {
                if (empty($this->registrantContact[$field])) {
                    $this->errorMessage = 'Please fill in all required contact fields.';
                    return;
                }
            }

            if (!filter_var($this->registrantContact['email'], FILTER_VALIDATE_EMAIL)) {
                $this->errorMessage = 'Please enter a valid email address.';
                return;
            }
        }
    }

    protected function validateStep4(): void
    {
        if (!$this->useDefaultNameservers) {
            $validNameservers = array_filter($this->nameservers, fn($ns) => !empty($ns));
            
            if (count($validNameservers) < 2) {
                $this->errorMessage = 'Please provide at least 2 nameservers.';
                return;
            }

            foreach ($validNameservers as $ns) {
                if (!$this->isValidNameserver($ns)) {
                    $this->errorMessage = "Invalid nameserver format: {$ns}";
                    return;
                }
            }
        }
    }

    protected function isValidNameserver(string $nameserver): bool
    {
        return (bool) preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $nameserver);
    }

    public function updatedYears(): void
    {
        $this->calculatePrice();
    }

    protected function calculatePrice(): void
    {
        if (!$this->tld || !$this->years) {
            return;
        }

        $user = Auth::user();
        $this->price = $this->pricingService->calculateFinalPrice(
            $this->tld,
            $user->partner_id,
            \App\Enums\PriceAction::REGISTER,
            $this->years
        );
    }

    protected function extractTld(): bool
    {
        $parts = explode('.', $this->domainName);
        if (count($parts) < 2) {
            return false;
        }

        $extension = end($parts);
        $this->tld = Tld::where('extension', $extension)
            ->where('is_active', true)
            ->first();

        return $this->tld !== null;
    }

    public function register(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        // Final validation
        if (!$this->acceptTerms) {
            $this->errorMessage = 'You must accept the terms and conditions to proceed.';
            return;
        }

        $this->isProcessing = true;

        try {
            $user = Auth::user();

            $data = [
                'domain_name' => $this->domainName,
                'years' => $this->years,
                'client_id' => $user->id,
                'partner_id' => $user->partner_id,
                'auto_renew' => $this->autoRenew,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];

            // Add contacts if not using defaults
            if (!$this->useDefaultContacts) {
                $contacts = [
                    'registrant' => $this->registrantContact,
                ];

                if (!$this->useSameForAll) {
                    // For now, use same contact for all types
                    // In a full implementation, you'd have separate forms
                }

                $data['contacts'] = $contacts;
            }

            // Add nameservers if not using defaults
            if (!$this->useDefaultNameservers) {
                $data['nameservers'] = array_filter($this->nameservers, fn($ns) => !empty($ns));
            }

            $result = $this->registrationService->register($data);

            if ($result['success']) {
                // Queue email notification
                SendDomainRegistrationEmail::dispatch($result['domain'], $result['invoice']);

                $this->successMessage = $result['message'];
                
                // Redirect to domain details or dashboard
                $this->redirect(route('client.dashboard'), navigate: true);
            } else {
                $this->errorMessage = $result['message'];
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Registration failed: ' . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function addNameserver(): void
    {
        if (count($this->nameservers) < 4) {
            $this->nameservers[] = '';
        }
    }

    public function removeNameserver(int $index): void
    {
        if (count($this->nameservers) > 2) {
            unset($this->nameservers[$index]);
            $this->nameservers = array_values($this->nameservers);
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.client.domain.register-domain');
    }
}
