<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        // Calculate total if not provided
        static::creating(function (InvoiceItem $item) {
            if (empty($item->total)) {
                $item->total = $item->quantity * $item->unit_price;
            }
        });

        // Prevent updates if invoice is not draft
        static::updating(function (InvoiceItem $item) {
            if ($item->invoice->status !== \App\Enums\InvoiceStatus::Draft) {
                throw new \Exception('Invoice items cannot be modified after invoice is issued');
            }
        });

        // Prevent deletion if invoice is not draft
        static::deleting(function (InvoiceItem $item) {
            if ($item->invoice->status !== \App\Enums\InvoiceStatus::Draft) {
                throw new \Exception('Invoice items cannot be deleted after invoice is issued');
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the referenced model (polymorphic)
     */
    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            return $this->morphTo('reference', 'reference_type', 'reference_id');
        }
        return null;
    }
}
