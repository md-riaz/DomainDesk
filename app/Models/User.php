<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'partner_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->role === Role::Client && $user->partner_id === null) {
                throw new \InvalidArgumentException('Clients must have a partner_id');
            }
        });
    }

    // Relationships

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(User::class, 'partner_id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'client_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    // Role Check Methods

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isPartner(): bool
    {
        return $this->role === Role::Partner;
    }

    public function isClient(): bool
    {
        return $this->role === Role::Client;
    }

    // Query Scopes

    public function scopeWhereSuperAdmin(Builder $query): Builder
    {
        return $query->where('role', Role::SuperAdmin->value);
    }

    public function scopeWherePartner(Builder $query): Builder
    {
        return $query->where('role', Role::Partner->value);
    }

    public function scopeWhereClient(Builder $query): Builder
    {
        return $query->where('role', Role::Client->value);
    }

    public function scopeWhereRole(Builder $query, Role $role): Builder
    {
        return $query->where('role', $role->value);
    }

    public function scopeForPartner(Builder $query, int $partnerId): Builder
    {
        return $query->where('partner_id', $partnerId);
    }
}
