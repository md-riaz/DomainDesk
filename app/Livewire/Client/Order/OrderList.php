<?php

namespace App\Livewire\Client\Order;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OrderList extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $partner = currentPartner();
        $client = Auth::user();

        $query = Order::where('partner_id', $partner->id)
            ->where('client_id', $client->id)
            ->with(['items'])
            ->orderByDesc('created_at');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $query->where('order_number', 'like', '%' . $this->search . '%');
        }

        $orders = $query->paginate(15);

        $statistics = [
            'total' => Order::where('client_id', $client->id)->where('partner_id', $partner->id)->count(),
            'pending' => Order::where('client_id', $client->id)->where('partner_id', $partner->id)->where('status', 'pending')->count(),
            'completed' => Order::where('client_id', $client->id)->where('partner_id', $partner->id)->where('status', 'completed')->count(),
            'draft' => Order::where('client_id', $client->id)->where('partner_id', $partner->id)->where('status', 'draft')->count(),
        ];

        return view('livewire.client.order.order-list', [
            'orders' => $orders,
            'statistics' => $statistics,
        ])->layout('layouts.client');
    }
}
