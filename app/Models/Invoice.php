<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'partner_id',
        'client_id',
        'status',
        'subtotal',
        'tax',
        'total',
        'issued_at',
        'paid_at',
        'due_at',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        // Generate invoice number if not provided
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->partner_id);
            }
        });

        // Prevent updates after issued (except status changes)
        static::updating(function (Invoice $invoice) {
            $original = $invoice->getOriginal();
            $originalStatus = is_string($original['status']) 
                ? $original['status'] 
                : $original['status']?->value;
            
            if ($invoice->isDirty(['subtotal', 'tax', 'total', 'issued_at']) && 
                $originalStatus !== InvoiceStatus::Draft->value) {
                throw new \Exception('Invoice amounts cannot be modified after being issued');
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Generate unique invoice number
     */
    public static function generateInvoiceNumber(int $partnerId): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        // Get the last invoice number for this partner
        $lastInvoice = static::where('partner_id', $partnerId)
            ->where('invoice_number', 'like', "{$prefix}-{$partnerId}-{$year}{$month}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastInvoice) {
            // Extract sequence number and increment
            $parts = explode('-', $lastInvoice->invoice_number);
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
    }

    /**
     * Issue the invoice
     */
    public function issue(): bool
    {
        if (!$this->status->canBeModified()) {
            throw new \Exception('Only draft invoices can be issued');
        }

        return $this->update([
            'status' => InvoiceStatus::Issued,
            'issued_at' => now(),
            'due_at' => $this->due_at ?? now()->addDays(30),
        ]);
    }

    /**
     * Mark invoice as paid and create wallet transaction
     */
    public function markAsPaid(?int $userId = null): bool
    {
        if (!$this->status->canBePaid()) {
            throw new \Exception('Invoice cannot be marked as paid in current status');
        }

        return DB::transaction(function () use ($userId) {
            // Update invoice status
            $this->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
            ]);

            // Debit from partner wallet
            $wallet = Wallet::where('partner_id', $this->partner_id)->firstOrFail();
            $wallet->debit(
                $this->total,
                "Payment for invoice {$this->invoice_number}",
                Invoice::class,
                $this->id,
                $userId
            );

            return true;
        });
    }

    /**
     * Refund the invoice
     */
    public function refund(?int $userId = null): bool
    {
        if (!$this->status->canBeRefunded()) {
            throw new \Exception('Only paid invoices can be refunded');
        }

        return DB::transaction(function () use ($userId) {
            // Update invoice status
            $this->update([
                'status' => InvoiceStatus::Refunded,
            ]);

            // Credit back to partner wallet
            $wallet = Wallet::where('partner_id', $this->partner_id)->firstOrFail();
            $wallet->refund(
                $this->total,
                "Refund for invoice {$this->invoice_number}",
                Invoice::class,
                $this->id,
                $userId
            );

            return true;
        });
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, InvoiceStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to get overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatus::Issued->value)
            ->where('due_at', '<', now());
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Issued && 
               $this->due_at && 
               $this->due_at->isPast();
    }
}
