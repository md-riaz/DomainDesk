<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
    ];

    protected $appends = [
        'balance',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get the wallet balance (computed from transactions)
     */
    public function getBalanceAttribute(): float
    {
        return (float) $this->transactions()
            ->selectRaw('
                SUM(CASE 
                    WHEN type IN ("credit", "refund") THEN amount
                    WHEN type = "debit" THEN -amount
                    WHEN type = "adjustment" THEN amount
                    ELSE 0
                END) as balance
            ')
            ->value('balance') ?? 0.00;
    }

    /**
     * Add credit to wallet (with transaction locking)
     */
    public function credit(
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $description, $referenceType, $referenceId, $createdBy) {
            // Lock wallet row to prevent race conditions
            DB::table('wallets')->where('id', $this->id)->lockForUpdate()->first();

            return $this->transactions()->create([
                'partner_id' => $this->partner_id,
                'type' => 'credit',
                'amount' => $amount,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Debit from wallet (with transaction locking and balance check)
     */
    public function debit(
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null,
        bool $allowNegative = false
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $description, $referenceType, $referenceId, $createdBy, $allowNegative) {
            // Lock wallet row to prevent race conditions
            DB::table('wallets')->where('id', $this->id)->lockForUpdate()->first();

            // Check balance if negative balance not allowed
            if (!$allowNegative && $this->balance < $amount) {
                throw new \Exception('Insufficient wallet balance');
            }

            return $this->transactions()->create([
                'partner_id' => $this->partner_id,
                'type' => 'debit',
                'amount' => $amount,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Refund to wallet
     */
    public function refund(
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $description, $referenceType, $referenceId, $createdBy) {
            DB::table('wallets')->where('id', $this->id)->lockForUpdate()->first();

            return $this->transactions()->create([
                'partner_id' => $this->partner_id,
                'type' => 'refund',
                'amount' => $amount,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Make adjustment to wallet (can be positive or negative)
     */
    public function adjust(
        float $amount,
        string $description,
        ?int $createdBy = null
    ): WalletTransaction {
        return DB::transaction(function () use ($amount, $description, $createdBy) {
            DB::table('wallets')->where('id', $this->id)->lockForUpdate()->first();

            return $this->transactions()->create([
                'partner_id' => $this->partner_id,
                'type' => 'adjustment',
                'amount' => $amount,
                'description' => $description,
                'created_by' => $createdBy,
            ]);
        });
    }
}
