<?php

namespace App\Livewire\Client\Order;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderComplete extends Component
{
    public Order $order;

    public function mount(int $order): void
    {
        $partner = currentPartner();
        $client = Auth::user();

        $this->order = Order::where('id', $order)
            ->where('partner_id', $partner->id)
            ->where('client_id', $client->id)
            ->with(['items.tld', 'items.domain', 'invoice'])
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.client.order.order-complete')->layout('layouts.client');
    }
}
