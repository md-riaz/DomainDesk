<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Registrar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_class',
        'credentials',
        'is_active',
        'is_default',
        'last_sync_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted:json',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function tlds(): HasMany
    {
        return $this->hasMany(Tld::class);
    }

    public function activeTlds(): HasMany
    {
        return $this->hasMany(Tld::class)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function markAsDefault(): void
    {
        // Remove default from all other registrars
        static::where('is_default', true)->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
    }

    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }
}
