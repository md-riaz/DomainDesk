<?php

namespace App\Enums;

enum OrderItemType: string
{
    case DomainRegistration = 'domain_registration';
    case DomainRenewal = 'domain_renewal';
    case DomainTransfer = 'domain_transfer';

    public function label(): string
    {
        return match ($this) {
            self::DomainRegistration => 'Domain Registration',
            self::DomainRenewal => 'Domain Renewal',
            self::DomainTransfer => 'Domain Transfer',
        };
    }

    public function description(string $domainName, int $years): string
    {
        return match ($this) {
            self::DomainRegistration => "Register {$domainName} for {$years} " . str_plural('year', $years),
            self::DomainRenewal => "Renew {$domainName} for {$years} " . str_plural('year', $years),
            self::DomainTransfer => "Transfer {$domainName} ({$years} " . str_plural('year', $years) . " included)",
        };
    }
}
