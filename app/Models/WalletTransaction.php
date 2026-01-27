<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'partner_id',
        'type',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
    ];

    /**
     * Prevent updates on wallet transactions (append-only ledger)
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \Exception('Wallet transactions cannot be updated (append-only ledger)');
        });

        static::deleting(function () {
            throw new \Exception('Wallet transactions cannot be deleted (append-only ledger)');
        });
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * Scope to filter by transaction type
     */
    public function scopeOfType($query, TransactionType $type)
    {
        return $query->where('type', $type->value);
    }

    /**
     * Scope to get credits (includes refunds)
     */
    public function scopeCredits($query)
    {
        return $query->whereIn('type', [TransactionType::Credit->value, TransactionType::Refund->value]);
    }

    /**
     * Scope to get debits
     */
    public function scopeDebits($query)
    {
        return $query->where('type', TransactionType::Debit->value);
    }
}
