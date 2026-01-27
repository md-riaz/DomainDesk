<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerBranding extends Model
{
    use HasFactory;

    protected $table = 'partner_branding';

    protected $fillable = [
        'partner_id',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'login_background_path',
        'email_sender_name',
        'email_sender_email',
        'support_email',
        'support_phone',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
