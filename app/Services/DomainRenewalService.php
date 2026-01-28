<?php

namespace App\Services;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PriceAction;
use App\Exceptions\RegistrarException;
use App\Jobs\SendRenewalEmailJob;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Models\Wallet;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DomainRenewalService
{
    protected PricingService $pricingService;

    // Business constants
    const MAX_EARLY_RENEWAL_DAYS = 90;
    const GRACE_PERIOD_DAYS = 30;
    const REDEMPTION_PERIOD_DAYS = 30;
    const MIN_RENEWAL_YEARS = 1;
    const MAX_RENEWAL_YEARS = 10;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Renew a domain manually
     *
     * @param Domain $domain
     * @param int $years Number of years to renew (1-10)
     * @param int|null $userId User performing the renewal
     * @return array ['success' => bool, 'domain' => Domain, 'invoice' => Invoice, 'message' => string]
     * @throws \Exception
     */
    public function renewDomain(Domain $domain, int $years, ?int $userId = null): array
    {
        // Validate renewal years
        $this->validateRenewalYears($years);

        // Check if domain is renewable
        $renewabilityCheck = $this->checkRenewability($domain);
        if (!$renewabilityCheck['renewable']) {
            return [
                'success' => false,
                'domain' => $domain,
                'invoice' => null,
                'message' => $renewabilityCheck['reason'],
            ];
        }

        // Get partner
        $partner = Partner::findOrFail($domain->partner_id);

        try {
            return DB::transaction(function () use ($domain, $years, $partner, $userId, $renewabilityCheck) {
                // Step 1: Calculate renewal price
                $tld = $domain->name ? $this->extractTld($domain->name) : null;
                if (!$tld) {
                    throw new \Exception('Unable to extract TLD from domain name');
                }

                $renewalPrice = $this->pricingService->calculateFinalPrice(
                    $tld,
                    $partner,
                    PriceAction::RENEW,
                    $years
                );

                if ($renewalPrice === null) {
                    throw new \Exception('Renewal price not available for this domain');
                }

                // Check if in grace period (may have additional cost)
                $gracePeriodSurcharge = 0;
                if ($renewabilityCheck['in_grace_period'] ?? false) {
                    $gracePeriodSurcharge = bcmul($renewalPrice, '0.20', 2); // 20% surcharge
                    $renewalPrice = bcadd($renewalPrice, $gracePeriodSurcharge, 2);
                }

                // Step 2: Check wallet balance
                $wallet = Wallet::where('partner_id', $partner->id)->firstOrFail();
                if ($wallet->balance < (float) $renewalPrice) {
                    return [
                        'success' => false,
                        'domain' => $domain,
                        'invoice' => null,
                        'message' => 'Insufficient wallet balance. Please add funds to continue.',
                    ];
                }

                // Step 3: Generate renewal invoice
                $invoice = $this->createRenewalInvoice($domain, $partner, $years, $renewalPrice, $gracePeriodSurcharge);

                // Step 4: Debit wallet
                try {
                    $wallet->debit(
                        $renewalPrice,
                        "Domain renewal for {$domain->name} ({$years} year" . ($years > 1 ? 's' : '') . ")",
                        Invoice::class,
                        $invoice->id,
                        $userId
                    );
                } catch (\Exception $e) {
                    // If debit fails, mark invoice as failed
                    $invoice->update(['status' => InvoiceStatus::Failed]);
                    throw new \Exception('Failed to debit wallet: ' . $e->getMessage());
                }

                // Step 5: Call registrar renewal API
                try {
                    $registrarInstance = RegistrarFactory::make($domain->registrar_id);
                    $registrarResponse = $registrarInstance->renew($domain->name, $years);

                    if (!$registrarResponse['success']) {
                        throw new RegistrarException(
                            message: 'Registrar renewal failed: ' . ($registrarResponse['message'] ?? 'Unknown error'),
                            registrarName: $registrarInstance->getName()
                        );
                    }
                } catch (RegistrarException $e) {
                    // Rollback: Refund wallet and mark invoice as failed
                    $wallet->refund(
                        $renewalPrice,
                        "Refund for failed renewal of {$domain->name}",
                        Invoice::class,
                        $invoice->id,
                        $userId
                    );
                    $invoice->update(['status' => InvoiceStatus::Failed]);

                    Log::error('Domain renewal registrar failure', [
                        'domain_id' => $domain->id,
                        'domain_name' => $domain->name,
                        'years' => $years,
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'success' => false,
                        'domain' => $domain,
                        'invoice' => $invoice,
                        'message' => 'Registrar renewal failed: ' . $e->getMessage(),
                    ];
                }

                // Step 6: Update domain expiry date
                $newExpiryDate = $this->calculateNewExpiryDate($domain->expires_at, $years);
                $domain->update([
                    'expires_at' => $newExpiryDate,
                    'status' => DomainStatus::Active,
                ]);

                // Step 7: Mark invoice as paid
                $invoice->update([
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => now(),
                ]);

                // Step 8: Create audit log
                AuditLog::create([
                    'user_id' => $userId,
                    'partner_id' => $domain->partner_id,
                    'action' => 'domain_renewed',
                    'auditable_type' => Domain::class,
                    'auditable_id' => $domain->id,
                    'old_values' => ['expires_at' => $domain->getOriginal('expires_at')],
                    'new_values' => [
                        'expires_at' => $newExpiryDate->toIso8601String(),
                        'years' => $years,
                        'renewal_price' => $renewalPrice,
                        'grace_period_surcharge' => $gracePeriodSurcharge,
                        'invoice_id' => $invoice->id,
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // Step 9: Queue email notification
                SendRenewalEmailJob::dispatch($domain, $invoice, 'success');

                Log::info('Domain renewal successful', [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'years' => $years,
                    'new_expiry_date' => $newExpiryDate->toIso8601String(),
                    'invoice_id' => $invoice->id,
                ]);

                return [
                    'success' => true,
                    'domain' => $domain->fresh(),
                    'invoice' => $invoice,
                    'message' => "Domain {$domain->name} successfully renewed for {$years} year" . ($years > 1 ? 's' : ''),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Domain renewal failed', [
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'years' => $years,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process automatic renewals for domains expiring soon
     *
     * @param int $leadTimeDays Number of days before expiry to attempt renewal
     * @param int|null $partnerId Filter by specific partner
     * @return array ['processed' => int, 'succeeded' => int, 'failed' => int, 'results' => array]
     */
    public function processAutoRenewals(int $leadTimeDays = 7, ?int $partnerId = null): array
    {
        $cutoffDate = now()->addDays($leadTimeDays);

        $query = Domain::where('auto_renew', true)
            ->where('status', DomainStatus::Active)
            ->where('expires_at', '<=', $cutoffDate)
            ->where('expires_at', '>', now()); // Not yet expired

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        $domains = $query->get();

        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($domains as $domain) {
            $results['processed']++;

            try {
                // Attempt renewal for 1 year
                $result = $this->renewDomain($domain, 1);

                if ($result['success']) {
                    $results['succeeded']++;
                    $results['results'][] = [
                        'domain_id' => $domain->id,
                        'domain_name' => $domain->name,
                        'status' => 'success',
                        'message' => $result['message'],
                    ];
                } else {
                    $results['failed']++;
                    $results['results'][] = [
                        'domain_id' => $domain->id,
                        'domain_name' => $domain->name,
                        'status' => 'failed',
                        'message' => $result['message'],
                    ];

                    // Send failure notification
                    SendRenewalEmailJob::dispatch($domain, null, 'auto_renew_failed', $result['message']);
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['results'][] = [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];

                Log::error('Auto-renewal error', [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'error' => $e->getMessage(),
                ]);

                // Send failure notification
                SendRenewalEmailJob::dispatch($domain, null, 'auto_renew_failed', $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Check if a domain is renewable
     *
     * @param Domain $domain
     * @return array ['renewable' => bool, 'reason' => string, 'in_grace_period' => bool]
     */
    public function checkRenewability(Domain $domain): array
    {
        // Check if domain has expiry date
        if (!$domain->expires_at) {
            return [
                'renewable' => false,
                'reason' => 'Domain does not have an expiry date',
                'in_grace_period' => false,
            ];
        }

        $daysUntilExpiry = $domain->daysUntilExpiry();
        $daysAfterExpiry = $domain->expires_at->isPast() ? abs($daysUntilExpiry) : 0;

        // Check if too early (more than 90 days before expiry)
        if ($daysUntilExpiry > self::MAX_EARLY_RENEWAL_DAYS) {
            return [
                'renewable' => false,
                'reason' => "Domain cannot be renewed more than " . self::MAX_EARLY_RENEWAL_DAYS . " days before expiry",
                'in_grace_period' => false,
            ];
        }

        // Check if in redemption period (cannot be renewed, must be restored)
        if ($daysAfterExpiry > self::GRACE_PERIOD_DAYS && $daysAfterExpiry <= (self::GRACE_PERIOD_DAYS + self::REDEMPTION_PERIOD_DAYS)) {
            return [
                'renewable' => false,
                'reason' => 'Domain is in redemption period. Please contact support for restoration.',
                'in_grace_period' => false,
            ];
        }

        // Check if beyond redemption period (domain is deleted)
        if ($daysAfterExpiry > (self::GRACE_PERIOD_DAYS + self::REDEMPTION_PERIOD_DAYS)) {
            return [
                'renewable' => false,
                'reason' => 'Domain has been deleted and cannot be renewed',
                'in_grace_period' => false,
            ];
        }

        // Check if in grace period (0-30 days after expiry)
        $inGracePeriod = $domain->expires_at->isPast() && $daysAfterExpiry <= self::GRACE_PERIOD_DAYS;

        return [
            'renewable' => true,
            'reason' => $inGracePeriod ? 'Domain is in grace period. Additional fees may apply.' : 'Domain is renewable',
            'in_grace_period' => $inGracePeriod,
        ];
    }

    /**
     * Calculate renewal price for a domain
     *
     * @param Domain $domain
     * @param int $years
     * @return string|null Price as string or null if not available
     */
    public function calculateRenewalPrice(Domain $domain, int $years): ?string
    {
        $tld = $this->extractTld($domain->name);
        if (!$tld) {
            return null;
        }

        $partner = Partner::find($domain->partner_id);
        if (!$partner) {
            return null;
        }

        $basePrice = $this->pricingService->calculateFinalPrice(
            $tld,
            $partner,
            PriceAction::RENEW,
            $years
        );

        // Add grace period surcharge if applicable
        $renewabilityCheck = $this->checkRenewability($domain);
        if ($renewabilityCheck['in_grace_period'] ?? false) {
            $gracePeriodSurcharge = bcmul($basePrice, '0.20', 2);
            return bcadd($basePrice, $gracePeriodSurcharge, 2);
        }

        return $basePrice;
    }

    /**
     * Create renewal invoice
     */
    protected function createRenewalInvoice(Domain $domain, Partner $partner, int $years, string $price, float $gracePeriodSurcharge = 0): Invoice
    {
        $invoice = Invoice::create([
            'partner_id' => $partner->id,
            'client_id' => $domain->client_id,
            'status' => InvoiceStatus::Issued,
            'subtotal' => $price,
            'tax' => 0,
            'total' => $price,
            'issued_at' => now(),
            'due_at' => now(),
        ]);

        $description = "Domain renewal: {$domain->name} ({$years} year" . ($years > 1 ? 's' : '') . ")";
        if ($gracePeriodSurcharge > 0) {
            $description .= " - Includes grace period surcharge";
        }

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $price,
            'total' => $price,
        ]);

        return $invoice;
    }

    /**
     * Calculate new expiry date after renewal
     */
    protected function calculateNewExpiryDate($currentExpiryDate, int $years): \DateTime|\Carbon\Carbon
    {
        // If already expired, start from today
        if ($currentExpiryDate->isPast()) {
            return now()->addYears($years);
        }

        // Otherwise, extend from current expiry date
        return $currentExpiryDate->copy()->addYears($years);
    }

    /**
     * Extract TLD from domain name
     */
    protected function extractTld(string $domainName): ?\App\Models\Tld
    {
        $parts = explode('.', strtolower(trim($domainName)));
        if (count($parts) < 2) {
            return null;
        }

        $tldString = end($parts);
        return \App\Models\Tld::where('extension', $tldString)->first();
    }

    /**
     * Validate renewal years
     */
    protected function validateRenewalYears(int $years): void
    {
        if ($years < self::MIN_RENEWAL_YEARS || $years > self::MAX_RENEWAL_YEARS) {
            throw new \InvalidArgumentException(
                "Renewal years must be between " . self::MIN_RENEWAL_YEARS . " and " . self::MAX_RENEWAL_YEARS
            );
        }
    }
}
