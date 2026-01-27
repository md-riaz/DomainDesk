<?php

namespace App\Enums;

enum DnsRecordType: string
{
    case A = 'A';
    case AAAA = 'AAAA';
    case CNAME = 'CNAME';
    case MX = 'MX';
    case TXT = 'TXT';
    case NS = 'NS';

    public function label(): string
    {
        return $this->value;
    }

    public function supportsPriority(): bool
    {
        return $this === self::MX;
    }
}
