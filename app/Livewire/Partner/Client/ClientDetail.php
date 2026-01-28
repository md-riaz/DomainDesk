<?php

namespace App\Livewire\Partner\Client;

use App\Models\Domain;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Client Detail Component
 * 
 * Displays detailed information about a client including overview, domains, and invoices.
 * Provides functionality to suspend/activate clients and reset passwords.
 * Uses tabbed navigation with pagination for domains and invoices.
 */
class ClientDetail extends Component
{
    use WithPagination;

    const ITEMS_PER_PAGE = 10;
    const PASSWORD_LENGTH = 12;

    public User $client;
    public string $activeTab = 'overview';
    public ?string $resetPasswordValue = null;

    public function mount($clientId)
    {
        $this->client = User::whereClient()
            ->where('partner_id', currentPartner()->id)
            ->withTrashed()
            ->withCount('domains', 'invoices')
            ->findOrFail($clientId);
    }

    public function suspendClient()
    {
        $this->client->delete();
        $this->client->refresh();

        session()->flash('success', 'Client suspended successfully.');
        
        $this->dispatch('client-updated');
    }

    public function activateClient()
    {
        $this->client->restore();
        $this->client->refresh();

        session()->flash('success', 'Client activated successfully.');
        
        $this->dispatch('client-updated');
    }

    public function resetPassword()
    {
        $newPassword = \Illuminate\Support\Str::random(self::PASSWORD_LENGTH);
        $this->client->update(['password' => bcrypt($newPassword)]);
        $this->client->refresh();

        // Store password in component state instead of session for security
        $this->resetPasswordValue = $newPassword;
        
        $this->dispatch('password-reset', password: $newPassword);
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        $data = [
            'client' => $this->client,
        ];

        if ($this->activeTab === 'domains') {
            $data['domains'] = Domain::where('client_id', $this->client->id)
                ->where('partner_id', currentPartner()->id)
                ->with('client')
                ->latest()
                ->paginate(self::ITEMS_PER_PAGE);
        } elseif ($this->activeTab === 'invoices') {
            $data['invoices'] = Invoice::where('client_id', $this->client->id)
                ->where('partner_id', currentPartner()->id)
                ->with('items')
                ->latest()
                ->paginate(self::ITEMS_PER_PAGE);
            
            $data['totalSpent'] = Invoice::where('client_id', $this->client->id)
                ->where('partner_id', currentPartner()->id)
                ->where('status', \App\Enums\InvoiceStatus::Paid)
                ->sum('total');
        }

        return view('livewire.partner.client.client-detail', $data)
            ->layout('layouts.partner');
    }
}
