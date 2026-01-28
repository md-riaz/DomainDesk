<?php

namespace App\Services;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PriceAction;
use App\Exceptions\RegistrarException;
use App\Jobs\SendDomainTransferEmailJob;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Registrar\RegistrarFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DomainTransferService
{
    protected PricingService $pricingService;

    // Business constants
    const MIN_DOMAIN_AGE_DAYS = 60; // ICANN rule
    const TRANSFER_INCLUDES_YEARS = 1; // Transfer includes 1 year renewal
    const TRANSFER_TIMEOUT_DAYS = 7;
    const CANCELLATION_WINDOW_DAYS = 5;
    const AUTH_CODE_MIN_LENGTH = 6;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Initiate a domain transfer-in
     *
     * @param array $data Transfer data including:
     *   - domain_name: string
     *   - auth_code: string
     *   - client_id: int
     *   - partner_id: int
     *   - registrar_id: int (optional, uses default if not provided)
     *   - auto_renew: bool (optional, default false)
     *   - ip_address: string (optional)
     *   - user_agent: string (optional)
     * 
     * @return array ['success' => bool, 'domain' => Domain|null, 'invoice' => Invoice|null, 'message' => string]
     * @throws \Exception
     */
    public function initiateTransferIn(array $data): array
    {
        // Validate input
        $this->validateTransferData($data);

        $domainName = strtolower(trim($data['domain_name']));
        $authCode = trim($data['auth_code']);
        $client = User::findOrFail($data['client_id']);
        $partner = Partner::findOrFail($data['partner_id']);
        $autoRenew = $data['auto_renew'] ?? false;
        $ipAddress = $data['ip_address'] ?? request()->ip();
        $userAgent = $data['user_agent'] ?? request()->userAgent();

        // Verify client belongs to partner
        if ($client->partner_id !== $partner->id) {
            throw new \Exception('Client does not belong to the specified partner');
        }

        // Get or set registrar
        $registrarId = $data['registrar_id'] ?? Registrar::where('is_default', true)->where('is_active', true)->firstOrFail()->id;
        $registrar = Registrar::findOrFail($registrarId);

        // Check if domain already exists
        $existingDomain = Domain::where('name', $domainName)
            ->where('partner_id', $partner->id)
            ->first();

        if ($existingDomain) {
            return [
                'success' => false,
                'domain' => null,
                'invoice' => null,
                'message' => 'Domain already exists in your account',
            ];
        }

        // Extract TLD
        $tld = $this->extractTld($domainName);
        if (!$tld) {
            throw new \Exception('Invalid domain name or TLD not supported');
        }

        try {
            return DB::transaction(function () use (
                $domainName,
                $authCode,
                $client,
                $partner,
                $registrar,
                $tld,
                $autoRenew,
                $ipAddress,
                $userAgent
            ) {
                // Step 1: Check domain eligibility
                $eligibilityCheck = $this->checkTransferEligibility($domainName, $registrar);
                if (!$eligibilityCheck['eligible']) {
                    return [
                        'success' => false,
                        'domain' => null,
                        'invoice' => null,
                        'message' => $eligibilityCheck['reason'] ?? 'Domain is not eligible for transfer',
                    ];
                }

                // Step 2: Calculate transfer price (includes 1 year renewal)
                $transferPrice = $this->pricingService->calculateFinalPrice(
                    $tld,
                    $partner,
                    PriceAction::TRANSFER,
                    self::TRANSFER_INCLUDES_YEARS
                );

                if ($transferPrice === null) {
                    throw new \Exception('Transfer price not available for this domain');
                }

                // Step 3: Check wallet balance
                $wallet = Wallet::where('partner_id', $partner->id)->firstOrFail();
                if ($wallet->balance < (float) $transferPrice) {
                    return [
                        'success' => false,
                        'domain' => null,
                        'invoice' => null,
                        'message' => 'Insufficient wallet balance. Please add funds to continue.',
                    ];
                }

                // Step 4: Create invoice
                $invoice = Invoice::create([
                    'partner_id' => $partner->id,
                    'client_id' => $client->id,
                    'invoice_number' => Invoice::generateInvoiceNumber($partner->id),
                    'status' => InvoiceStatus::Pending,
                    'subtotal' => $transferPrice,
                    'tax_amount' => 0,
                    'total' => $transferPrice,
                    'due_date' => now()->addDays(7),
                    'notes' => "Domain transfer: {$domainName}",
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'partner_id' => $partner->id,
                    'description' => "Domain Transfer: {$domainName} (includes 1 year renewal)",
                    'quantity' => 1,
                    'unit_price' => $transferPrice,
                    'total' => $transferPrice,
                    'metadata' => [
                        'domain' => $domainName,
                        'years' => self::TRANSFER_INCLUDES_YEARS,
                        'action' => 'transfer',
                    ],
                ]);

                // Step 5: Debit wallet
                try {
                    $wallet->debit(
                        amount: (float) $transferPrice,
                        description: "Domain transfer: {$domainName}",
                        referenceType: Invoice::class,
                        referenceId: $invoice->id,
                        createdBy: $client->id
                    );

                    $invoice->update(['status' => InvoiceStatus::Paid]);
                } catch (\Exception $e) {
                    $invoice->update(['status' => InvoiceStatus::Failed]);
                    throw new \Exception('Failed to debit wallet: ' . $e->getMessage());
                }

                // Step 6: Initiate transfer with registrar
                try {
                    $registrarInstance = RegistrarFactory::make($registrar->id);
                    $transferResult = $registrarInstance->transfer($domainName, $authCode);

                    if (!$transferResult['success']) {
                        // Refund wallet on registrar failure
                        $wallet->refund(
                            amount: (float) $transferPrice,
                            description: "Refund for failed domain transfer: {$domainName}",
                            referenceType: Invoice::class,
                            referenceId: $invoice->id,
                            createdBy: $client->id
                        );

                        $invoice->update(['status' => InvoiceStatus::Refunded]);

                        return [
                            'success' => false,
                            'domain' => null,
                            'invoice' => $invoice,
                            'message' => $transferResult['message'] ?? 'Transfer initiation failed at registrar',
                        ];
                    }
                } catch (RegistrarException $e) {
                    // Refund wallet on registrar error
                    $wallet->refund(
                        amount: (float) $transferPrice,
                        description: "Refund for failed domain transfer: {$domainName}",
                        referenceType: Invoice::class,
                        referenceId: $invoice->id,
                        createdBy: $client->id
                    );

                    $invoice->update(['status' => InvoiceStatus::Refunded]);

                    Log::error('Domain transfer registrar error', [
                        'domain' => $domainName,
                        'partner_id' => $partner->id,
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'success' => false,
                        'domain' => null,
                        'invoice' => $invoice,
                        'message' => 'Registrar error: ' . $e->getMessage(),
                    ];
                }

                // Step 7: Create domain record
                $domain = Domain::create([
                    'name' => $domainName,
                    'client_id' => $client->id,
                    'partner_id' => $partner->id,
                    'registrar_id' => $registrar->id,
                    'status' => DomainStatus::PendingTransfer,
                    'registered_at' => null, // Will be set when transfer completes
                    'expires_at' => now()->addYear(), // Estimated, will be updated
                    'auto_renew' => $autoRenew,
                    'auth_code' => $authCode,
                    'transfer_initiated_at' => now(),
                    'transfer_metadata' => [
                        'initiated_by' => $client->id,
                        'ip_address' => $ipAddress,
                        'user_agent' => $userAgent,
                        'registrar_response' => $transferResult['data'] ?? null,
                        'estimated_completion' => now()->addDays(self::TRANSFER_TIMEOUT_DAYS)->toDateString(),
                    ],
                ]);

                // Step 8: Create audit log
                AuditLog::create([
                    'partner_id' => $partner->id,
                    'user_id' => $client->id,
                    'action' => 'domain.transfer.initiated',
                    'auditable_type' => Domain::class,
                    'auditable_id' => $domain->id,
                    'old_values' => null,
                    'new_values' => [
                        'domain' => $domainName,
                        'status' => DomainStatus::PendingTransfer->value,
                    ],
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ]);

                // Step 9: Queue email notification
                dispatch(new SendDomainTransferEmailJob(
                    $domain->id,
                    'initiated',
                    $client->email
                ));

                Log::info('Domain transfer initiated', [
                    'domain' => $domainName,
                    'domain_id' => $domain->id,
                    'partner_id' => $partner->id,
                    'client_id' => $client->id,
                    'price' => $transferPrice,
                ]);

                return [
                    'success' => true,
                    'domain' => $domain->fresh(),
                    'invoice' => $invoice->fresh(),
                    'message' => 'Domain transfer initiated successfully. This may take 5-7 days to complete.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Domain transfer error', [
                'domain' => $domainName,
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Check and update transfer status
     */
    public function checkTransferStatus(Domain $domain): array
    {
        if (!$domain->isTransferring()) {
            return [
                'success' => false,
                'domain' => $domain,
                'message' => 'Domain is not in transferring state',
            ];
        }

        try {
            $registrarInstance = RegistrarFactory::make($domain->registrar_id);
            $statusResult = $registrarInstance->getTransferStatus($domain->name);

            if (!$statusResult['success']) {
                return [
                    'success' => false,
                    'domain' => $domain,
                    'message' => 'Failed to check transfer status',
                ];
            }

            $statusData = $statusResult['data'] ?? [];
            $this->updateTransferStatus($domain, $statusData);

            return [
                'success' => true,
                'domain' => $domain->fresh(),
                'message' => 'Transfer status updated',
                'status_data' => $statusData,
            ];
        } catch (\Exception $e) {
            Log::error('Transfer status check error', [
                'domain_id' => $domain->id,
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'domain' => $domain,
                'message' => 'Error checking transfer status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update domain transfer status based on registrar data
     */
    protected function updateTransferStatus(Domain $domain, array $statusData): void
    {
        $registrarStatus = strtolower($statusData['status'] ?? '');
        $previousStatus = $domain->status;

        $newStatus = match ($registrarStatus) {
            'pending', 'initiated' => DomainStatus::PendingTransfer,
            'in_progress', 'processing' => DomainStatus::TransferInProgress,
            'approved', 'awaiting_completion' => DomainStatus::TransferApproved,
            'completed', 'success' => DomainStatus::TransferCompleted,
            'failed', 'rejected', 'denied' => DomainStatus::TransferFailed,
            'cancelled', 'canceled' => DomainStatus::TransferCancelled,
            default => $domain->status,
        };

        $updateData = [
            'status' => $newStatus,
            'transfer_status_message' => $statusData['message'] ?? null,
            'transfer_metadata' => array_merge(
                $domain->transfer_metadata ?? [],
                [
                    'last_status_check' => now()->toIso8601String(),
                    'registrar_status' => $statusData,
                ]
            ),
        ];

        // If transfer completed, update domain details
        if ($newStatus === DomainStatus::TransferCompleted) {
            $updateData['transfer_completed_at'] = now();
            $updateData['registered_at'] = $statusData['registered_at'] ?? $domain->transfer_initiated_at;
            $updateData['expires_at'] = $statusData['expires_at'] ?? now()->addYear();
            $updateData['auth_code'] = null; // Clear auth code after successful transfer
            
            // Send completion email
            dispatch(new SendDomainTransferEmailJob(
                $domain->id,
                'completed',
                $domain->client->email
            ));
        }

        // If transfer failed, send failure email
        if ($newStatus === DomainStatus::TransferFailed && $previousStatus !== DomainStatus::TransferFailed) {
            dispatch(new SendDomainTransferEmailJob(
                $domain->id,
                'failed',
                $domain->client->email
            ));
        }

        $domain->update($updateData);

        // Log status change if different
        if ($previousStatus !== $newStatus) {
            AuditLog::create([
                'partner_id' => $domain->partner_id,
                'user_id' => null,
                'action' => 'domain.transfer.status_changed',
                'auditable_type' => Domain::class,
                'auditable_id' => $domain->id,
                'old_values' => ['status' => $previousStatus->value],
                'new_values' => ['status' => $newStatus->value],
                'ip_address' => null,
                'user_agent' => 'System',
            ]);
        }
    }

    /**
     * Cancel a domain transfer
     */
    public function cancelTransfer(Domain $domain, int $userId): array
    {
        if (!$domain->canCancelTransfer()) {
            return [
                'success' => false,
                'domain' => $domain,
                'message' => 'Transfer cannot be cancelled in current state',
            ];
        }

        // Check if within cancellation window
        if ($domain->transfer_initiated_at && 
            $domain->transfer_initiated_at->diffInDays(now()) > self::CANCELLATION_WINDOW_DAYS) {
            return [
                'success' => false,
                'domain' => $domain,
                'message' => 'Cancellation window has expired (5 days from initiation)',
            ];
        }

        try {
            return DB::transaction(function () use ($domain, $userId) {
                // Attempt to cancel with registrar
                try {
                    $registrarInstance = RegistrarFactory::make($domain->registrar_id);
                    $cancelResult = $registrarInstance->cancelTransfer($domain->name);

                    if (!$cancelResult['success']) {
                        return [
                            'success' => false,
                            'domain' => $domain,
                            'message' => 'Failed to cancel transfer with registrar',
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Transfer cancellation error', [
                        'domain_id' => $domain->id,
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'success' => false,
                        'domain' => $domain,
                        'message' => 'Error cancelling transfer: ' . $e->getMessage(),
                    ];
                }

                // Update domain status
                $oldStatus = $domain->status;
                $domain->update([
                    'status' => DomainStatus::TransferCancelled,
                    'transfer_status_message' => 'Transfer cancelled by client',
                    'transfer_metadata' => array_merge(
                        $domain->transfer_metadata ?? [],
                        [
                            'cancelled_at' => now()->toIso8601String(),
                            'cancelled_by' => $userId,
                        ]
                    ),
                ]);

                // Find and refund invoice
                $invoice = Invoice::where('partner_id', $domain->partner_id)
                    ->where('client_id', $domain->client_id)
                    ->where('notes', 'like', "%{$domain->name}%")
                    ->where('status', InvoiceStatus::Paid)
                    ->latest()
                    ->first();

                if ($invoice) {
                    $wallet = Wallet::where('partner_id', $domain->partner_id)->first();
                    if ($wallet) {
                        $wallet->refund(
                            amount: (float) $invoice->total,
                            description: "Refund for cancelled domain transfer: {$domain->name}",
                            referenceType: Invoice::class,
                            referenceId: $invoice->id,
                            createdBy: $userId
                        );
                        $invoice->update(['status' => InvoiceStatus::Refunded]);
                    }
                }

                // Create audit log
                AuditLog::create([
                    'partner_id' => $domain->partner_id,
                    'user_id' => $userId,
                    'action' => 'domain.transfer.cancelled',
                    'auditable_type' => Domain::class,
                    'auditable_id' => $domain->id,
                    'old_values' => ['status' => $oldStatus->value],
                    'new_values' => ['status' => DomainStatus::TransferCancelled->value],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                Log::info('Domain transfer cancelled', [
                    'domain_id' => $domain->id,
                    'domain' => $domain->name,
                    'cancelled_by' => $userId,
                ]);

                return [
                    'success' => true,
                    'domain' => $domain->fresh(),
                    'message' => 'Transfer cancelled successfully and wallet refunded',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Cancel transfer error', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate/get auth code for transfer out
     */
    public function getAuthCodeForTransferOut(Domain $domain, int $userId): array
    {
        // Verify domain is owned by user
        if ($domain->client_id !== $userId) {
            return [
                'success' => false,
                'auth_code' => null,
                'message' => 'You are not authorized to view this domain\'s auth code',
            ];
        }

        // Domain must be active
        if (!$domain->status->isActive()) {
            return [
                'success' => false,
                'auth_code' => null,
                'message' => 'Domain must be active to generate auth code',
            ];
        }

        try {
            $registrarInstance = RegistrarFactory::make($domain->registrar_id);
            
            // Unlock domain (required for transfer)
            $registrarInstance->unlock($domain->name);

            // Get auth code from registrar
            $authCodeResult = $registrarInstance->getAuthCode($domain->name);

            if (!$authCodeResult['success']) {
                return [
                    'success' => false,
                    'auth_code' => null,
                    'message' => 'Failed to generate auth code',
                ];
            }

            $authCode = $authCodeResult['data']['auth_code'] ?? null;

            // Store auth code
            $domain->update(['auth_code' => $authCode]);

            // Create audit log
            AuditLog::create([
                'partner_id' => $domain->partner_id,
                'user_id' => $userId,
                'action' => 'domain.auth_code.generated',
                'auditable_type' => Domain::class,
                'auditable_id' => $domain->id,
                'old_values' => null,
                'new_values' => ['auth_code_generated' => true],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            Log::info('Auth code generated for transfer out', [
                'domain_id' => $domain->id,
                'domain' => $domain->name,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'auth_code' => $authCode,
                'message' => 'Auth code generated successfully. Domain has been unlocked.',
            ];
        } catch (\Exception $e) {
            Log::error('Auth code generation error', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'auth_code' => null,
                'message' => 'Error generating auth code: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate transfer data
     */
    protected function validateTransferData(array $data): void
    {
        if (empty($data['domain_name'])) {
            throw new \InvalidArgumentException('Domain name is required');
        }

        if (empty($data['auth_code'])) {
            throw new \InvalidArgumentException('Auth code is required');
        }

        if (strlen($data['auth_code']) < self::AUTH_CODE_MIN_LENGTH) {
            throw new \InvalidArgumentException('Auth code must be at least ' . self::AUTH_CODE_MIN_LENGTH . ' characters');
        }

        if (empty($data['client_id'])) {
            throw new \InvalidArgumentException('Client ID is required');
        }

        if (empty($data['partner_id'])) {
            throw new \InvalidArgumentException('Partner ID is required');
        }
    }

    /**
     * Check if domain is eligible for transfer
     */
    protected function checkTransferEligibility(string $domain, Registrar $registrar): array
    {
        try {
            $registrarInstance = RegistrarFactory::make($registrar->id);
            $domainInfo = $registrarInstance->getInfo($domain);

            if (!$domainInfo['success']) {
                return [
                    'eligible' => false,
                    'reason' => 'Unable to retrieve domain information',
                ];
            }

            $info = $domainInfo['data'] ?? [];

            // Check if domain is locked
            if ($info['locked'] ?? true) {
                return [
                    'eligible' => false,
                    'reason' => 'Domain is locked. Please unlock at current registrar first.',
                ];
            }

            // Check domain age (60-day rule)
            if (isset($info['registered_at'])) {
                $registeredDate = Carbon::parse($info['registered_at']);
                if ($registeredDate->diffInDays(now()) < self::MIN_DOMAIN_AGE_DAYS) {
                    return [
                        'eligible' => false,
                        'reason' => 'Domain must be at least 60 days old to transfer (ICANN rule)',
                    ];
                }
            }

            return [
                'eligible' => true,
                'reason' => null,
            ];
        } catch (\Exception $e) {
            Log::warning('Transfer eligibility check failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            // Allow transfer to proceed if we can't check eligibility
            return [
                'eligible' => true,
                'reason' => null,
            ];
        }
    }

    /**
     * Extract TLD from domain name
     */
    protected function extractTld(string $domain): ?Tld
    {
        $parts = explode('.', $domain);
        if (count($parts) < 2) {
            return null;
        }

        $tldName = end($parts);
        return Tld::where('extension', $tldName)->where('is_active', true)->first();
    }
}
