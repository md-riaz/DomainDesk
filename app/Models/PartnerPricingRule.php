<?php

namespace App\Models;

use App\Enums\MarkupType;
use App\Enums\PriceAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'tld_id',
        'markup_type',
        'markup_value',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'markup_type' => MarkupType::class,
        'markup_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function tld(): BelongsTo
    {
        return $this->belongsTo(Tld::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Apply markup to a base price
     */
    public function applyMarkup(float $basePrice): string
    {
        $result = match ($this->markup_type) {
            MarkupType::FIXED => bcadd((string)$basePrice, (string)$this->markup_value, 4),
            MarkupType::PERCENTAGE => bcadd(
                (string)$basePrice,
                bcmul((string)$basePrice, bcdiv((string)$this->markup_value, '100', 6), 6),
                4
            ),
        };

        // Round to 2 decimal places using standard rounding
        $rounded = round((float)$result, 2);
        return number_format($rounded, 2, '.', '');
    }

    /**
     * Calculate final price for a TLD with this rule
     */
    public function calculateFinalPrice(Tld $tld, PriceAction|string $action, int $years): ?string
    {
        $basePrice = $tld->getBasePrice($action, $years);
        
        if ($basePrice === null) {
            return null;
        }

        return $this->applyMarkup($basePrice);
    }

    /**
     * Get the most specific pricing rule for a partner, TLD, and duration
     * Priority: TLD + Duration > TLD > Global + Duration > Global
     */
    public static function getMostSpecificRule(
        int $partnerId,
        ?int $tldId,
        int $duration
    ): ?self {
        // Try TLD-specific with duration match
        if ($tldId) {
            $rule = static::active()
                ->where('partner_id', $partnerId)
                ->where('tld_id', $tldId)
                ->where('duration', $duration)
                ->first();
            
            if ($rule) {
                return $rule;
            }

            // Try TLD-specific without duration
            $rule = static::active()
                ->where('partner_id', $partnerId)
                ->where('tld_id', $tldId)
                ->whereNull('duration')
                ->first();
            
            if ($rule) {
                return $rule;
            }
        }

        // Try global rule with duration match
        $rule = static::active()
            ->where('partner_id', $partnerId)
            ->whereNull('tld_id')
            ->where('duration', $duration)
            ->first();
        
        if ($rule) {
            return $rule;
        }

        // Try global rule without duration
        return static::active()
            ->where('partner_id', $partnerId)
            ->whereNull('tld_id')
            ->whereNull('duration')
            ->first();
    }
}
