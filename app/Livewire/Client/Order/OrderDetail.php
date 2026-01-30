<?php

namespace App\Livewire\Client\Order;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderDetail extends Component
{
    public Order $order;

    public function mount(int $orderId): void
    {
        $partner = currentPartner();
        $client = Auth::user();

        $this->order = Order::where('id', $orderId)
            ->where('partner_id', $partner->id)
            ->where('client_id', $client->id)
            ->with(['items.tld', 'items.domain', 'invoice'])
            ->firstOrFail();
    }

    public function cancelOrder(): void
    {
        try {
            $this->order->cancel();
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Order cancelled successfully']);
            $this->redirect(route('client.orders.list'));
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.client.order.order-detail')->layout('layouts.client');
    }
}
