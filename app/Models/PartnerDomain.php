<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'domain',
        'is_primary',
        'is_verified',
        'dns_status',
        'ssl_status',
        'verified_at',
        'ssl_issued_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'ssl_issued_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    public function isPending(): bool
    {
        return $this->dns_status === 'pending';
    }

    public function hasFailed(): bool
    {
        return $this->dns_status === 'failed';
    }

    public function getCnameTarget(): string
    {
        return config('app.url');
    }
}
