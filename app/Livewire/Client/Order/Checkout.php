<?php

namespace App\Livewire\Client\Order;

use App\Enums\ContactType;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Checkout extends Component
{
    public ?Order $order = null;
    public int $currentStep = 1;
    
    // Contact information
    public array $registrantContact = [];
    public bool $useSameForAll = true;
    
    // Nameservers
    public bool $useDefaultNameservers = true;
    public array $nameservers = ['', ''];
    
    // Terms
    public bool $acceptTerms = false;
    
    // UI state
    public bool $isProcessing = false;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    protected OrderService $orderService;

    public function boot(OrderService $orderService): void
    {
        $this->orderService = $orderService;
    }

    public function mount(): void
    {
        $this->loadOrder();
        $this->initializeContactForm();
        
        if (!$this->order || $this->order->items->count() === 0) {
            $this->redirect(route('client.order.cart'));
        }
    }

    protected function loadOrder(): void
    {
        $partner = currentPartner();
        $client = Auth::user();

        $this->order = Order::where('partner_id', $partner->id)
            ->where('client_id', $client->id)
            ->where('status', 'draft')
            ->with(['items.tld'])
            ->first();
    }

    protected function initializeContactForm(): void
    {
        $user = Auth::user();
        $nameParts = explode(' ', $user->name);

        $this->registrantContact = [
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
            'email' => $user->email,
            'phone' => '',
            'organization' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'country' => 'BD',
        ];
    }

    public function nextStep(): void
    {
        if ($this->currentStep < 3) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function submit(): void
    {
        if (!$this->acceptTerms) {
            $this->errorMessage = 'Please accept the terms and conditions';
            return;
        }

        $this->isProcessing = true;
        $this->errorMessage = null;

        try {
            // Prepare configuration for each item
            $configuration = [
                'contacts' => $this->prepareContacts(),
                'nameservers' => $this->useDefaultNameservers ? [] : array_filter($this->nameservers),
                'auto_renew' => false,
            ];

            // Update each item with configuration
            foreach ($this->order->items as $item) {
                $this->orderService->updateItemConfiguration($item, $configuration);
            }

            // Submit order
            $this->order->submit();

            // Process order
            $results = $this->orderService->processOrder($this->order, Auth::id());

            // Redirect to success page
            $this->redirect(route('client.order.complete', ['order' => $this->order->id]));

        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->isProcessing = false;
        }
    }

    protected function prepareContacts(): array
    {
        $contacts = [
            ContactType::Registrant->value => $this->registrantContact,
        ];

        if ($this->useSameForAll) {
            $contacts[ContactType::Admin->value] = $this->registrantContact;
            $contacts[ContactType::Tech->value] = $this->registrantContact;
            $contacts[ContactType::Billing->value] = $this->registrantContact;
        }

        return $contacts;
    }

    public function render()
    {
        return view('livewire.client.order.checkout')->layout('layouts.client');
    }
}
