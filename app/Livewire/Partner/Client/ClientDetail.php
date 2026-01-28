<?php

namespace App\Livewire\Partner\Client;

use App\Models\Domain;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class ClientDetail extends Component
{
    use WithPagination;

    public User $client;
    public string $activeTab = 'overview';

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
        $this->authorize('update', $this->client);

        $this->client->delete();

        session()->flash('success', 'Client suspended successfully.');
        
        $this->dispatch('client-updated');
    }

    public function activateClient()
    {
        $this->authorize('update', $this->client);

        $this->client->restore();

        session()->flash('success', 'Client activated successfully.');
        
        $this->dispatch('client-updated');
    }

    public function resetPassword()
    {
        $this->authorize('update', $this->client);

        $newPassword = \Illuminate\Support\Str::random(12);
        $this->client->update(['password' => bcrypt($newPassword)]);

        session()->flash('success', "Password reset successfully. New password: {$newPassword}");
        
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
                ->paginate(10);
        } elseif ($this->activeTab === 'invoices') {
            $data['invoices'] = Invoice::where('client_id', $this->client->id)
                ->where('partner_id', currentPartner()->id)
                ->with('items')
                ->latest()
                ->paginate(10);
            
            $data['totalSpent'] = Invoice::where('client_id', $this->client->id)
                ->where('partner_id', currentPartner()->id)
                ->where('status', \App\Enums\InvoiceStatus::Paid)
                ->sum('total');
        }

        return view('livewire.partner.client.client-detail', $data)
            ->layout('layouts.partner');
    }
}
