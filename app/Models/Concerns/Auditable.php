<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->auditAction('created', null, $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            if (empty($changes) || (count($changes) === 1 && isset($changes['updated_at']))) {
                return;
            }

            $old = [];
            $new = [];
            
            foreach ($changes as $key => $value) {
                if ($key === 'updated_at') {
                    continue;
                }
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }

            $model->auditAction('updated', $old, $new);
        });

        static::deleted(function ($model) {
            $action = $model->isForceDeleting() ? 'deleted' : 'soft_deleted';
            $model->auditAction($action, $model->getAuditableAttributes(), null);
        });
    }

    protected function auditAction(string $action, ?array $oldValues, ?array $newValues): void
    {
        $user = Auth::user();
        
        AuditLog::create([
            'user_id' => $user?->id,
            'partner_id' => $this->partner_id ?? $user?->partner_id ?? null,
            'action' => $action,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        
        // Remove timestamps and sensitive data
        $excludedFields = array_merge(
            ['created_at', 'updated_at', 'deleted_at'],
            $this->getHidden()
        );
        
        foreach ($excludedFields as $field) {
            unset($attributes[$field]);
        }
        
        return $attributes;
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
