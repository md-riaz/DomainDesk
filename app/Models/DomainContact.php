<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'organization',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    protected $casts = [
        'type' => ContactType::class,
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function scopeOfType($query, ContactType $type)
    {
        return $query->where('type', $type);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
