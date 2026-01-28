<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'email_enabled',
        'dashboard_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'dashboard_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getDefaultPreferences(): array
    {
        return [
            'domain_registered' => ['email' => true, 'dashboard' => true],
            'domain_renewed' => ['email' => true, 'dashboard' => true],
            'renewal_reminder_30' => ['email' => true, 'dashboard' => true],
            'renewal_reminder_15' => ['email' => true, 'dashboard' => true],
            'renewal_reminder_7' => ['email' => true, 'dashboard' => true],
            'renewal_reminder_1' => ['email' => true, 'dashboard' => true],
            'domain_expired' => ['email' => true, 'dashboard' => true],
            'invoice_issued' => ['email' => true, 'dashboard' => true],
            'payment_received' => ['email' => true, 'dashboard' => true],
            'low_balance' => ['email' => true, 'dashboard' => true],
            'transfer_initiated' => ['email' => true, 'dashboard' => true],
            'transfer_completed' => ['email' => true, 'dashboard' => true],
        ];
    }
}
