<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case PartiallyCompleted = 'partially_completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending Payment',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::PartiallyCompleted => 'Partially Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'yellow',
            self::Processing => 'blue',
            self::Completed => 'green',
            self::Failed => 'red',
            self::Cancelled => 'gray',
            self::PartiallyCompleted => 'orange',
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Draft, self::Pending]);
    }

    public function canBeModified(): bool
    {
        return $this === self::Draft;
    }

    public function isProcessable(): bool
    {
        return $this === self::Pending;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled, self::PartiallyCompleted]);
    }
}
