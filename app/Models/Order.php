<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToPartner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, Auditable, BelongsToPartner;

    protected $fillable = [
        'order_number',
        'partner_id',
        'client_id',
        'status',
        'subtotal',
        'tax',
        'total',
        'invoice_id',
        'submitted_at',
        'completed_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        // Generate order number if not provided
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber($order->partner_id);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(int $partnerId): string
    {
        $prefix = 'ORD';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        // Get the last order number for this partner
        $lastOrder = static::where('partner_id', $partnerId)
            ->where('order_number', 'like', "{$prefix}-{$partnerId}-{$year}{$month}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastOrder) {
            // Extract sequence number and increment
            $parts = explode('-', $lastOrder->order_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%d-%s%s-%04d', $prefix, $partnerId, $year, $month, $sequence);
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total');
        $this->total = $this->subtotal + $this->tax;
        $this->save();
    }

    /**
     * Submit the order for processing
     */
    public function submit(): bool
    {
        if (!$this->status->canBeModified()) {
            throw new \Exception('Only draft orders can be submitted');
        }

        if ($this->items()->count() === 0) {
            throw new \Exception('Cannot submit an empty order');
        }

        $this->calculateTotals();

        return $this->update([
            'status' => OrderStatus::Pending,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Cancel the order
     */
    public function cancel(?string $reason = null): bool
    {
        if (!$this->status->canBeCancelled()) {
            throw new \Exception('This order cannot be cancelled');
        }

        return $this->update([
            'status' => OrderStatus::Cancelled,
            'notes' => $this->notes . "\n\nCancelled: " . ($reason ?? 'No reason provided'),
        ]);
    }

    /**
     * Mark order as processing
     */
    public function markAsProcessing(): bool
    {
        if (!$this->status->isProcessable()) {
            throw new \Exception('Only pending orders can be processed');
        }

        return $this->update([
            'status' => OrderStatus::Processing,
        ]);
    }

    /**
     * Mark order as completed
     */
    public function markAsCompleted(): bool
    {
        $allCompleted = $this->items()->where('status', '!=', 'completed')->count() === 0;
        $anyCompleted = $this->items()->where('status', 'completed')->count() > 0;

        $status = $allCompleted ? OrderStatus::Completed : 
                 ($anyCompleted ? OrderStatus::PartiallyCompleted : OrderStatus::Failed);

        return $this->update([
            'status' => $status,
            'completed_at' => now(),
        ]);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to get recent orders
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get order statistics
     */
    public function getStatistics(): array
    {
        $items = $this->items;
        
        return [
            'total_items' => $items->count(),
            'completed_items' => $items->where('status', 'completed')->count(),
            'failed_items' => $items->where('status', 'failed')->count(),
            'pending_items' => $items->where('status', 'pending')->count(),
        ];
    }
}
