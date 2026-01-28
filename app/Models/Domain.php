<?php

namespace App\Models;

use App\Enums\DomainStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToPartner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToPartner;

    protected $fillable = [
        'name',
        'client_id',
        'partner_id',
        'registrar_id',
        'status',
        'registered_at',
        'expires_at',
        'auto_renew',
        'last_synced_at',
        'sync_metadata',
    ];

    protected $casts = [
        'status' => DomainStatus::class,
        'registered_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
        'last_synced_at' => 'datetime',
        'sync_metadata' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(DomainContact::class);
    }

    public function nameservers(): HasMany
    {
        return $this->hasMany(DomainNameserver::class)->orderBy('order');
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DomainDnsRecord::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DomainDocument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', DomainStatus::Active);
    }

    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('status', DomainStatus::Active)
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->whereIn('status', [DomainStatus::Expired, DomainStatus::GracePeriod, DomainStatus::Redemption]);
    }

    public function scopeAutoRenew($query)
    {
        return $query->where('auto_renew', true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function daysUntilExpiry(): ?int
    {
        return $this->expires_at ? now()->diffInDays($this->expires_at, false) : null;
    }

    public function needsSync(int $minHoursSinceLastSync = 6): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }

        return $this->last_synced_at->diffInHours(now()) >= $minHoursSinceLastSync;
    }

    public function markAsSynced(array $metadata = []): void
    {
        $this->update([
            'last_synced_at' => now(),
            'sync_metadata' => $metadata,
        ]);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(Registrar::class);
    }
}
