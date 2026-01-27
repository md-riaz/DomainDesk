<?php

namespace App\Models;

use App\Enums\PriceAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TldPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tld_id',
        'action',
        'years',
        'price',
        'effective_date',
    ];

    protected $casts = [
        'action' => PriceAction::class,
        'price' => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function tld(): BelongsTo
    {
        return $this->belongsTo(Tld::class);
    }

    /**
     * Get price history for a specific TLD, action, and years
     */
    public static function getPriceHistory(int $tldId, PriceAction|string $action, int $years): array
    {
        $actionValue = $action instanceof PriceAction ? $action->value : $action;

        return static::where('tld_id', $tldId)
            ->where('action', $actionValue)
            ->where('years', $years)
            ->orderBy('effective_date', 'desc')
            ->get()
            ->map(fn($price) => [
                'price' => $price->price,
                'effective_date' => $price->effective_date->format('Y-m-d'),
            ])
            ->toArray();
    }

    /**
     * Get the price change percentage since last price
     */
    public function getPriceChange(): ?float
    {
        $previousPrice = static::where('tld_id', $this->tld_id)
            ->where('action', $this->action)
            ->where('years', $this->years)
            ->where('effective_date', '<', $this->effective_date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$previousPrice) {
            return null;
        }

        return (($this->price - $previousPrice->price) / $previousPrice->price) * 100;
    }
}
