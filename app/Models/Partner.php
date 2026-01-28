<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'email',
        'slug',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(PartnerDomain::class);
    }

    public function branding(): HasOne
    {
        return $this->hasOne(PartnerBranding::class);
    }

    public function users(): HasMany
    {
        // Bypass partner scope since Partner already ensures isolation
        return $this->hasMany(User::class)->withoutGlobalScope(\App\Scopes\PartnerScope::class);
    }

    public function clientDomains(): HasMany
    {
        // Bypass partner scope since Partner already ensures isolation
        return $this->hasMany(Domain::class)->withoutGlobalScope(\App\Scopes\PartnerScope::class);
    }

    public function primaryDomain(): HasOne
    {
        return $this->hasOne(PartnerDomain::class)->where('is_primary', true);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function invoices(): HasMany
    {
        // Bypass partner scope since Partner already ensures isolation
        return $this->hasMany(Invoice::class)->withoutGlobalScope(\App\Scopes\PartnerScope::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PartnerPricingRule::class);
    }

    public function activePricingRules(): HasMany
    {
        return $this->hasMany(PartnerPricingRule::class)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_active', true);
    }
}
