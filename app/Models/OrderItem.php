<?php

namespace App\Models;

use App\Enums\OrderItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'domain_name',
        'tld_id',
        'years',
        'unit_price',
        'quantity',
        'total',
        'status',
        'domain_id',
        'configuration',
        'error_message',
    ];

    protected $casts = [
        'type' => OrderItemType::class,
        'years' => 'integer',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'total' => 'decimal:2',
        'configuration' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        // Calculate total when creating/updating
        static::saving(function (OrderItem $item) {
            $item->total = $item->unit_price * $item->quantity;
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function tld(): BelongsTo
    {
        return $this->belongsTo(Tld::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Mark item as completed
     */
    public function markAsCompleted(?int $domainId = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'domain_id' => $domainId,
            'error_message' => null,
        ]);
    }

    /**
     * Mark item as failed
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark item as processing
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => 'processing',
        ]);
    }

    /**
     * Get item description
     */
    public function getDescription(): string
    {
        return $this->type->description($this->domain_name, $this->years);
    }

    /**
     * Check if item can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed';
    }
}
