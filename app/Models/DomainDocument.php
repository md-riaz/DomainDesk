<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DomainDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'domain_id',
        'document_type',
        'file_path',
        'original_filename',
        'file_size',
        'uploaded_by',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
        'verified_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function verify(User $verifier, ?string $notes = null): void
    {
        $this->update([
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'notes' => $notes,
        ]);
    }
}
