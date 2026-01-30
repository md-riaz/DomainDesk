<?php

namespace App\Livewire\Client\Order;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Cart extends Component
{
    public ?Order $order = null;
    public bool $isProcessing = false;
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    protected OrderService $orderService;

    public function boot(OrderService $orderService): void
    {
        $this->orderService = $orderService;
    }

    public function mount(): void
    {
        $this->loadCart();
    }

    protected function loadCart(): void
    {
        $partner = currentPartner();
        $client = Auth::user();

        $this->order = Order::where('partner_id', $partner->id)
            ->where('client_id', $client->id)
            ->where('status', 'draft')
            ->with(['items.tld'])
            ->first();
    }

    public function removeItem(int $itemId): void
    {
        try {
            $item = $this->order->items()->findOrFail($itemId);
            $this->orderService->removeItem($item);
            
            $this->successMessage = 'Item removed from cart';
            $this->loadCart();
            
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function updateYears(int $itemId, int $years): void
    {
        try {
            $item = $this->order->items()->findOrFail($itemId);
            $this->orderService->updateItemYears($item, $years);
            
            $this->successMessage = 'Years updated';
            $this->loadCart();
            
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function clearCart(): void
    {
        if (!$this->order) {
            return;
        }

        try {
            $this->order->items()->delete();
            $this->successMessage = 'Cart cleared';
            $this->loadCart();
            
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function proceedToCheckout(): void
    {
        if (!$this->order || $this->order->items->count() === 0) {
            $this->errorMessage = 'Your cart is empty';
            return;
        }

        $this->redirect(route('client.order.checkout'));
    }

    public function render()
    {
        return view('livewire.client.order.cart')->layout('layouts.client');
    }
}
