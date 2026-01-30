<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\Tld;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected DomainRegistrationService $domainRegistrationService;
    protected PricingService $pricingService;

    public function __construct(
        DomainRegistrationService $domainRegistrationService,
        PricingService $pricingService
    ) {
        $this->domainRegistrationService = $domainRegistrationService;
        $this->pricingService = $pricingService;
    }

    /**
     * Create a new order for a client
     */
    public function createOrder(Partner $partner, User $client): Order
    {
        return Order::create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'status' => OrderStatus::Draft,
        ]);
    }

    /**
     * Get or create draft order for a client
     */
    public function getOrCreateDraftOrder(Partner $partner, User $client): Order
    {
        $order = Order::where('partner_id', $partner->id)
            ->where('client_id', $client->id)
            ->where('status', OrderStatus::Draft)
            ->first();

        if (!$order) {
            $order = $this->createOrder($partner, $client);
        }

        return $order;
    }

    /**
     * Add domain to order
     */
    public function addDomainToOrder(
        Order $order,
        string $domainName,
        int $years = 1,
        OrderItemType $type = OrderItemType::DomainRegistration,
        array $configuration = []
    ): OrderItem {
        if (!$order->status->canBeModified()) {
            throw new \Exception('Cannot modify this order');
        }

        // Extract TLD
        $parts = explode('.', $domainName);
        if (count($parts) < 2) {
            throw new \Exception('Invalid domain name format');
        }
        
        $tldName = '.' . implode('.', array_slice($parts, 1));
        $tld = Tld::where('tld', $tldName)->where('is_enabled', true)->first();

        if (!$tld) {
            throw new \Exception('TLD not found or not enabled');
        }

        // Get pricing
        $price = $this->pricingService->getDomainPrice($tld, $order->partner_id, $years, $type->value);

        // Check if domain already in order
        $existingItem = $order->items()
            ->where('domain_name', $domainName)
            ->where('type', $type->value)
            ->first();

        if ($existingItem) {
            throw new \Exception('This domain is already in your order');
        }

        return $order->items()->create([
            'type' => $type,
            'domain_name' => strtolower($domainName),
            'tld_id' => $tld->id,
            'years' => $years,
            'unit_price' => $price,
            'quantity' => 1,
            'configuration' => $configuration,
            'status' => 'pending',
        ]);
    }

    /**
     * Remove item from order
     */
    public function removeItem(OrderItem $item): bool
    {
        if (!$item->order->status->canBeModified()) {
            throw new \Exception('Cannot modify this order');
        }

        return $item->delete();
    }

    /**
     * Update item configuration
     */
    public function updateItemConfiguration(OrderItem $item, array $configuration): bool
    {
        if (!$item->order->status->canBeModified()) {
            throw new \Exception('Cannot modify this order');
        }

        return $item->update(['configuration' => array_merge($item->configuration ?? [], $configuration)]);
    }

    /**
     * Update item years
     */
    public function updateItemYears(OrderItem $item, int $years): bool
    {
        if (!$item->order->status->canBeModified()) {
            throw new \Exception('Cannot modify this order');
        }

        if ($years < 1 || $years > 10) {
            throw new \Exception('Years must be between 1 and 10');
        }

        // Recalculate price
        $price = $this->pricingService->getDomainPrice(
            $item->tld,
            $item->order->partner_id,
            $years,
            $item->type->value
        );

        return $item->update([
            'years' => $years,
            'unit_price' => $price,
        ]);
    }

    /**
     * Process order - create invoice and process domains
     */
    public function processOrder(Order $order, ?int $userId = null): array
    {
        if (!$order->status->isProcessable()) {
            throw new \Exception('Order is not ready for processing');
        }

        return DB::transaction(function () use ($order, $userId) {
            // Mark order as processing
            $order->markAsProcessing();

            // Create invoice
            $invoice = $this->createInvoiceFromOrder($order);

            // Update order with invoice
            $order->update(['invoice_id' => $invoice->id]);

            // Process each item
            $results = [
                'success' => [],
                'failed' => [],
            ];

            foreach ($order->items as $item) {
                try {
                    $item->markAsProcessing();
                    
                    // Process based on type
                    $domain = match ($item->type) {
                        OrderItemType::DomainRegistration => $this->processDomainRegistration($item),
                        OrderItemType::DomainRenewal => $this->processDomainRenewal($item),
                        OrderItemType::DomainTransfer => $this->processDomainTransfer($item),
                    };

                    $item->markAsCompleted($domain?->id);
                    $results['success'][] = $item->domain_name;

                } catch (\Exception $e) {
                    Log::error('Order item processing failed', [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'domain' => $item->domain_name,
                        'error' => $e->getMessage(),
                    ]);

                    $item->markAsFailed($e->getMessage());
                    $results['failed'][] = [
                        'domain' => $item->domain_name,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Mark order as completed (or partially completed)
            $order->markAsCompleted();

            // If all items successful, mark invoice as paid
            if (empty($results['failed']) && !empty($results['success'])) {
                $invoice->markAsPaid($userId);
            }

            return $results;
        });
    }

    /**
     * Create invoice from order
     */
    protected function createInvoiceFromOrder(Order $order): Invoice
    {
        $invoice = Invoice::create([
            'partner_id' => $order->partner_id,
            'client_id' => $order->client_id,
            'status' => InvoiceStatus::Issued,
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
        ]);

        // Create invoice items from order items
        foreach ($order->items as $orderItem) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $orderItem->getDescription(),
                'details' => "Domain: {$orderItem->domain_name}",
                'quantity' => $orderItem->quantity,
                'unit_price' => $orderItem->unit_price,
                'amount' => $orderItem->total,
            ]);
        }

        // Calculate totals
        $invoice->calculateTotals();
        $invoice->save();

        return $invoice;
    }

    /**
     * Process domain registration
     */
    protected function processDomainRegistration(OrderItem $item)
    {
        $config = $item->configuration ?? [];
        
        return $this->domainRegistrationService->register(
            domainName: $item->domain_name,
            partnerId: $item->order->partner_id,
            clientId: $item->order->client_id,
            years: $item->years,
            contacts: $config['contacts'] ?? [],
            nameservers: $config['nameservers'] ?? [],
            autoRenew: $config['auto_renew'] ?? false
        );
    }

    /**
     * Process domain renewal (placeholder)
     */
    protected function processDomainRenewal(OrderItem $item)
    {
        // This would integrate with DomainRenewalService
        throw new \Exception('Domain renewal not yet implemented through orders');
    }

    /**
     * Process domain transfer (placeholder)
     */
    protected function processDomainTransfer(OrderItem $item)
    {
        // This would integrate with DomainTransferService
        throw new \Exception('Domain transfer not yet implemented through orders');
    }

    /**
     * Get order statistics for client
     */
    public function getClientStatistics(User $client, Partner $partner): array
    {
        $orders = Order::where('client_id', $client->id)
            ->where('partner_id', $partner->id);

        return [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->where('status', OrderStatus::Pending)->count(),
            'completed_orders' => $orders->where('status', OrderStatus::Completed)->count(),
            'total_spent' => $orders->where('status', OrderStatus::Completed)->sum('total'),
        ];
    }
}
