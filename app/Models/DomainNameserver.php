<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainNameserver extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'nameserver',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->order < 1 || $model->order > 4) {
                throw new \InvalidArgumentException('Nameserver order must be between 1 and 4');
            }
        });

        static::updating(function ($model) {
            if ($model->order < 1 || $model->order > 4) {
                throw new \InvalidArgumentException('Nameserver order must be between 1 and 4');
            }
        });
    }
}
