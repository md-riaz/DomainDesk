<?php

namespace App\Services;

use App\Enums\ContactType;
use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PriceAction;
use App\Exceptions\RegistrarException;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\DomainContact;
use App\Models\DomainNameserver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DomainRegistrationService
{
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Register a domain with complete transaction handling
     *
     * @param array $data Registration data including:
     *   - domain_name: string
     *   - years: int (1-10)
     *   - client_id: int
     *   - partner_id: int
     *   - registrar_id: int (optional, uses default if not provided)
     *   - auto_renew: bool (optional, default false)
     *   - contacts: array (optional, uses defaults if not provided)
     *   - nameservers: array (optional, uses defaults if not provided)
     *   - ip_address: string (optional)
     *   - user_agent: string (optional)
     * 
     * @return array ['success' => bool, 'domain' => Domain, 'invoice' => Invoice, 'message' => string]
     * @throws \Exception
     */
    public function register(array $data): array
    {
        // Validate input
        $this->validateRegistrationData($data);

        $domainName = strtolower(trim($data['domain_name']));
        $years = (int) $data['years'];
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

        // Extract TLD from domain name
        $tld = $this->extractTld($domainName);
        if (!$tld) {
            throw new \Exception('Invalid domain name or TLD not supported');
        }

        try {
            // Step 1: Validate domain availability
            $registrarInstance = RegistrarFactory::make($registrar->id);
            $isAvailable = $registrarInstance->checkAvailability($domainName);
            
            if (!$isAvailable) {
                return [
                    'success' => false,
                    'domain' => null,
                    'invoice' => null,
                    'message' => 'Domain is not available for registration',
                ];
            }

            // Step 2: Validate wallet balance
            $wallet = Wallet::where('partner_id', $partner->id)->firstOrFail();
            
            // Step 3: Calculate final price
            $finalPrice = $this->pricingService->calculateFinalPrice(
                $tld,
                $partner,
                PriceAction::REGISTER,
                $years
            );

            if ($finalPrice === null) {
                throw new \Exception('Unable to calculate price for this domain');
            }

            $finalPriceFloat = (float) $finalPrice;

            // Check wallet balance
            if ($wallet->balance < $finalPriceFloat) {
                return [
                    'success' => false,
                    'domain' => null,
                    'invoice' => null,
                    'message' => sprintf(
                        'Insufficient wallet balance. Required: $%.2f, Available: $%.2f',
                        $finalPriceFloat,
                        $wallet->balance
                    ),
                ];
            }

            // Begin database transaction
            return DB::transaction(function () use (
                $domainName,
                $years,
                $client,
                $partner,
                $registrar,
                $tld,
                $finalPrice,
                $finalPriceFloat,
                $autoRenew,
                $wallet,
                $registrarInstance,
                $data,
                $ipAddress,
                $userAgent
            ) {
                // Step 4: Generate invoice (draft status)
                $invoice = $this->createInvoice($client, $partner, $domainName, $years, $finalPrice);

                // Step 5: Debit wallet
                $wallet->debit(
                    $finalPriceFloat,
                    "Domain registration: {$domainName} for {$years} year(s)",
                    Invoice::class,
                    $invoice->id,
                    $client->id
                );

                // Step 6: Call registrar API to register
                try {
                    $contacts = $this->prepareContacts($data['contacts'] ?? [], $partner, $client);
                    $nameservers = $this->prepareNameservers($data['nameservers'] ?? [], $partner);

                    $registrarResponse = $registrarInstance->register([
                        'domain' => $domainName,
                        'years' => $years,
                        'contacts' => $contacts,
                        'nameservers' => $nameservers,
                        'auto_renew' => $autoRenew,
                    ]);

                    // Step 7: Create domain record in database
                    $domain = Domain::create([
                        'name' => $domainName,
                        'client_id' => $client->id,
                        'partner_id' => $partner->id,
                        'registrar_id' => $registrar->id,
                        'status' => DomainStatus::PendingRegistration,
                        'registered_at' => now(),
                        'expires_at' => now()->addYears($years),
                        'auto_renew' => $autoRenew,
                        'last_synced_at' => now(),
                        'sync_metadata' => [
                            'registration_response' => $registrarResponse,
                            'registered_via' => 'web',
                        ],
                    ]);

                    // Create domain contacts
                    $this->createDomainContacts($domain, $contacts);

                    // Create domain nameservers
                    $this->createDomainNameservers($domain, $nameservers);

                    // Link invoice item to domain (before marking invoice as paid)
                    $invoiceItem = $invoice->items()->first();
                    $invoiceItem->reference_type = Domain::class;
                    $invoiceItem->reference_id = $domain->id;
                    $invoiceItem->save();

                    // Step 8: Update invoice to issued/paid
                    $invoice->update([
                        'status' => InvoiceStatus::Paid,
                        'issued_at' => now(),
                        'paid_at' => now(),
                        'due_at' => now(),
                    ]);

                    // Step 9: Create audit log
                    $this->createAuditLog($domain, $client, $partner, $ipAddress, $userAgent, [
                        'action' => 'domain_registered',
                        'domain_name' => $domainName,
                        'years' => $years,
                        'price' => $finalPrice,
                        'invoice_id' => $invoice->id,
                        'registrar' => $registrar->name,
                    ]);

                    // Step 10: Queue email notification (will be handled by caller)
                    // Not implementing here as it would be dispatched outside transaction

                    Log::info('Domain registered successfully', [
                        'domain' => $domainName,
                        'client_id' => $client->id,
                        'partner_id' => $partner->id,
                        'invoice_id' => $invoice->id,
                    ]);

                    return [
                        'success' => true,
                        'domain' => $domain->fresh(['contacts', 'nameservers']),
                        'invoice' => $invoice->fresh(['items']),
                        'message' => 'Domain registered successfully',
                    ];

                } catch (RegistrarException $e) {
                    // Registrar failed - rollback wallet but keep invoice
                    Log::error('Registrar error during domain registration', [
                        'domain' => $domainName,
                        'client_id' => $client->id,
                        'error' => $e->getMessage(),
                    ]);

                    // Refund wallet
                    $wallet->refund(
                        $finalPriceFloat,
                        "Refund for failed registration: {$domainName}",
                        Invoice::class,
                        $invoice->id,
                        $client->id
                    );

                    // Mark invoice as failed
                    $invoice->update(['status' => InvoiceStatus::Failed]);

                    // Create audit log for failure
                    $this->createAuditLog(null, $client, $partner, $ipAddress, $userAgent, [
                        'action' => 'domain_registration_failed',
                        'domain_name' => $domainName,
                        'years' => $years,
                        'error' => $e->getMessage(),
                        'invoice_id' => $invoice->id,
                    ]);

                    // Return error instead of throwing to preserve wallet refund
                    return [
                        'success' => false,
                        'domain' => null,
                        'invoice' => $invoice->fresh(['items']),
                        'message' => 'Domain registration failed: ' . $e->getMessage(),
                    ];
                }
            });

        } catch (\Exception $e) {
            Log::error('Domain registration error', [
                'domain' => $domainName,
                'client_id' => $data['client_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate registration data
     */
    protected function validateRegistrationData(array $data): void
    {
        if (empty($data['domain_name'])) {
            throw new \InvalidArgumentException('Domain name is required');
        }

        if (empty($data['client_id'])) {
            throw new \InvalidArgumentException('Client ID is required');
        }

        if (empty($data['partner_id'])) {
            throw new \InvalidArgumentException('Partner ID is required');
        }

        $years = (int) ($data['years'] ?? 0);
        if ($years < 1 || $years > 10) {
            throw new \InvalidArgumentException('Registration period must be between 1 and 10 years');
        }
    }

    /**
     * Extract TLD model from domain name
     */
    protected function extractTld(string $domainName): ?Tld
    {
        $parts = explode('.', $domainName);
        if (count($parts) < 2) {
            return null;
        }

        $extension = end($parts);
        return Tld::where('extension', $extension)->where('is_active', true)->first();
    }

    /**
     * Create invoice for domain registration
     */
    protected function createInvoice(User $client, Partner $partner, string $domainName, int $years, string $price): Invoice
    {
        $invoice = Invoice::create([
            'partner_id' => $partner->id,
            'client_id' => $client->id,
            'status' => InvoiceStatus::Draft,
            'subtotal' => $price,
            'tax' => 0,
            'total' => $price,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Domain Registration: {$domainName} ({$years} year" . ($years > 1 ? 's' : '') . ")",
            'quantity' => 1,
            'unit_price' => $price,
            'total' => $price,
        ]);

        return $invoice;
    }

    /**
     * Prepare contact information for registrar
     */
    protected function prepareContacts(array $providedContacts, Partner $partner, User $client): array
    {
        $contacts = [];

        // If no contacts provided, use default contact from client/partner
        if (empty($providedContacts)) {
            $defaultContact = [
                'first_name' => explode(' ', $client->name)[0] ?? 'Admin',
                'last_name' => explode(' ', $client->name)[1] ?? 'User',
                'email' => $client->email,
                'phone' => '+1.5555555555',
                'organization' => $partner->name,
                'address' => '123 Main St',
                'city' => 'City',
                'state' => 'State',
                'postal_code' => '12345',
                'country' => 'US',
            ];

            foreach (ContactType::cases() as $type) {
                $contacts[$type->value] = $defaultContact;
            }
        } else {
            // Use provided contacts
            foreach (ContactType::cases() as $type) {
                $contacts[$type->value] = $providedContacts[$type->value] ?? ($providedContacts['registrant'] ?? []);
            }
        }

        return $contacts;
    }

    /**
     * Prepare nameservers for registrar
     */
    protected function prepareNameservers(array $providedNameservers, Partner $partner): array
    {
        if (!empty($providedNameservers)) {
            return array_slice($providedNameservers, 0, 4);
        }

        // Default nameservers
        return [
            'ns1.domaindesk.com',
            'ns2.domaindesk.com',
        ];
    }

    /**
     * Create domain contacts in database
     */
    protected function createDomainContacts(Domain $domain, array $contacts): void
    {
        foreach ($contacts as $type => $contactData) {
            DomainContact::create([
                'domain_id' => $domain->id,
                'type' => $type,
                'first_name' => $contactData['first_name'] ?? '',
                'last_name' => $contactData['last_name'] ?? '',
                'email' => $contactData['email'] ?? '',
                'phone' => $contactData['phone'] ?? '',
                'organization' => $contactData['organization'] ?? '',
                'address' => $contactData['address'] ?? '',
                'city' => $contactData['city'] ?? '',
                'state' => $contactData['state'] ?? '',
                'postal_code' => $contactData['postal_code'] ?? '',
                'country' => $contactData['country'] ?? 'US',
            ]);
        }
    }

    /**
     * Create domain nameservers in database
     */
    protected function createDomainNameservers(Domain $domain, array $nameservers): void
    {
        foreach ($nameservers as $index => $nameserver) {
            DomainNameserver::create([
                'domain_id' => $domain->id,
                'nameserver' => $nameserver,
                'order' => $index + 1,
            ]);
        }
    }

    /**
     * Create audit log entry
     */
    protected function createAuditLog(
        ?Domain $domain,
        User $client,
        Partner $partner,
        string $ipAddress,
        string $userAgent,
        array $data
    ): void {
        AuditLog::create([
            'user_id' => $client->id,
            'partner_id' => $partner->id,
            'action' => $data['action'],
            'auditable_type' => $domain ? Domain::class : null,
            'auditable_id' => $domain?->id,
            'old_values' => [],
            'new_values' => $data,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
