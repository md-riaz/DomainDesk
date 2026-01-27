<?php

namespace App\Models;

use App\Enums\PriceAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tld extends Model
{
    use HasFactory;

    protected $fillable = [
        'registrar_id',
        'extension',
        'min_years',
        'max_years',
        'supports_dns',
        'supports_whois_privacy',
        'is_active',
    ];

    protected $casts = [
        'supports_dns' => 'boolean',
        'supports_whois_privacy' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(Registrar::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(TldPrice::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PartnerPricingRule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the current base price for a specific action and years
     */
    public function getBasePrice(PriceAction|string $action, int $years): ?float
    {
        $actionValue = $action instanceof PriceAction ? $action->value : $action;

        $price = $this->prices()
            ->where('action', $actionValue)
            ->where('years', $years)
            ->where('effective_date', '<=', now()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->first();

        return $price?->price;
    }

    /**
     * Get all current prices for this TLD
     */
    public function getCurrentPrices(): array
    {
        $prices = [];
        
        foreach (PriceAction::cases() as $action) {
            for ($years = $this->min_years; $years <= $this->max_years; $years++) {
                $price = $this->getBasePrice($action, $years);
                if ($price !== null) {
                    $prices[$action->value][$years] = $price;
                }
            }
        }

        return $prices;
    }

    public function getFullExtension(): string
    {
        return '.' . $this->extension;
    }
}
