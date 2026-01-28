# DomainDesk System Architecture

**Version:** 1.0  
**Last Updated:** January 2025

## Table of Contents

1. [System Architecture Overview](#1-system-architecture-overview)
2. [Database Schema Documentation](#2-database-schema-documentation)
3. [Service Layer Patterns](#3-service-layer-patterns)
4. [Multi-Tenancy Design](#4-multi-tenancy-design)
5. [Security Architecture](#5-security-architecture)
6. [Performance Considerations](#6-performance-considerations)

---

## 1. System Architecture Overview

### 1.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Presentation Layer                        │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────────┐  │
│  │ Livewire UI    │  │ REST API       │  │ Admin Dashboard  │  │
│  │ (Partner)      │  │ (Optional)     │  │ (SuperAdmin)     │  │
│  └────────────────┘  └────────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Middleware Layer                            │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────────┐  │
│  │ PartnerContext │  │ Authentication │  │ Authorization    │  │
│  │ Middleware     │  │ (Sanctum)      │  │ (Policies)       │  │
│  └────────────────┘  └────────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                       Application Layer                          │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                     Service Layer                           │ │
│  │  ┌──────────────┐  ┌───────────────┐  ┌────────────────┐  │ │
│  │  │ Domain       │  │ Partner       │  │ Pricing        │  │ │
│  │  │ Services     │  │ Services      │  │ Service        │  │ │
│  │  └──────────────┘  └───────────────┘  └────────────────┘  │ │
│  │  ┌──────────────┐  ┌───────────────┐  ┌────────────────┐  │ │
│  │  │ DNS          │  │ Notification  │  │ Wallet         │  │ │
│  │  │ Service      │  │ Service       │  │ Service        │  │ │
│  │  └──────────────┘  └───────────────┘  └────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                  Registrar Abstraction Layer               │ │
│  │  ┌──────────────┐  ┌───────────────┐  ┌────────────────┐  │ │
│  │  │ Abstract     │  │ ResellerClub  │  │ Mock           │  │ │
│  │  │ Registrar    │  │ Registrar     │  │ Registrar      │  │ │
│  │  └──────────────┘  └───────────────┘  └────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                         Data Layer                               │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────────┐  │
│  │ Eloquent ORM   │  │ Global Scopes  │  │ Model Concerns   │  │
│  │ Models         │  │ (Partner)      │  │ (Auditable)      │  │
│  └────────────────┘  └────────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                     Infrastructure Layer                         │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────────┐  │
│  │ MySQL/MariaDB  │  │ Redis Cache    │  │ Queue System     │  │
│  │ Database       │  │                │  │ (Laravel Queue)  │  │
│  └────────────────┘  └────────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 Core Components

**Models Layer** (`app/Models/`)
- Domain entities with relationships
- Traits: `BelongsToPartner`, `Auditable`, `SoftDeletes`
- Eloquent casting and accessors/mutators

**Service Layer** (`app/Services/`)
- Business logic encapsulation
- Registrar abstraction
- Partner context management
- Domain operations (search, register, renew, transfer)

**Controller Layer** (`app/Http/Controllers/`)
- Request validation
- Service orchestration
- Response formatting

**Middleware Layer** (`app/Http/Middleware/`)
- Partner context resolution
- Authentication/Authorization
- Request logging

**Job Layer** (`app/Jobs/`)
- Asynchronous processing
- Email notifications
- Domain synchronization
- Registration/renewal workflows

### 1.3 External Integrations

- **ResellerClub API**: Primary registrar integration
- **Mock Registrar**: Development and testing
- **Email Services**: Laravel Mail system
- **Payment Gateways**: (Future integration)

---

## 2. Database Schema Documentation

### 2.1 Core Tables

#### **partners**
Master table for white-label partner instances.

```sql
- id: bigint PK
- name: varchar(255)
- email: varchar(255)
- slug: varchar(255) UNIQUE
- status: varchar(50)
- is_active: boolean
- created_at, updated_at, deleted_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (slug)

**Relationships:**
- Has many: users, domains (via partner_id), partner_domains, invoices
- Has one: branding, wallet, primary_domain

---

#### **users**
User accounts with role-based access (SuperAdmin, Partner, Client).

```sql
- id: bigint PK
- name: varchar(255)
- email: varchar(255) UNIQUE
- email_verified_at: timestamp
- password: varchar(255)
- role: enum('super_admin', 'partner', 'client')
- partner_id: bigint FK NULL
- remember_token: varchar(100)
- created_at, updated_at, deleted_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (email)
- INDEX (partner_id, role)
- INDEX (role)

**Foreign Keys:**
- partner_id → partners(id) ON DELETE CASCADE

**Constraints:**
- Clients MUST have partner_id (enforced in model)
- SuperAdmin and Partner roles have NULL partner_id

---

#### **domains**
Core domain registration tracking.

```sql
- id: bigint PK
- name: varchar(255) UNIQUE
- client_id: bigint FK (users)
- partner_id: bigint FK (partners)
- registrar_id: bigint FK (registrars) NULL
- status: enum(pending_registration, active, expired, grace_period, 
              redemption, suspended, transferred_out, 
              pending_transfer, transfer_in_progress, transfer_approved)
- registered_at: timestamp NULL
- expires_at: timestamp NULL
- auto_renew: boolean DEFAULT true
- last_synced_at: timestamp NULL
- sync_metadata: json NULL
- auth_code: text NULL (encrypted)
- transfer_initiated_at: timestamp NULL
- transfer_completed_at: timestamp NULL
- transfer_status_message: text NULL
- transfer_metadata: json NULL
- created_at, updated_at, deleted_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (name)
- INDEX (client_id, status)
- INDEX (partner_id, status)
- INDEX (expires_at, status)
- INDEX (status)
- INDEX (registrar_id)

**Foreign Keys:**
- client_id → users(id) ON DELETE CASCADE
- partner_id → partners(id) ON DELETE CASCADE
- registrar_id → registrars(id) ON DELETE SET NULL

---

#### **domain_contacts**
WHOIS contact information for domains.

```sql
- id: bigint PK
- domain_id: bigint FK
- type: enum(registrant, admin, tech, billing)
- first_name, last_name: varchar(255)
- email: varchar(255)
- phone: varchar(50)
- organization: varchar(255) NULL
- address, city: varchar(255)
- state: varchar(100) NULL
- postal_code: varchar(20)
- country: char(2) (ISO 3166-1 alpha-2)
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX (domain_id, type)

**Foreign Keys:**
- domain_id → domains(id) ON DELETE CASCADE

---

#### **domain_nameservers**
DNS nameserver records for domains.

```sql
- id: bigint PK
- domain_id: bigint FK
- nameserver: varchar(255)
- order: tinyint
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX (domain_id)

**Foreign Keys:**
- domain_id → domains(id) ON DELETE CASCADE

---

#### **domain_dns_records**
DNS zone records managed by the system.

```sql
- id: bigint PK
- domain_id: bigint FK
- type: enum(A, AAAA, CNAME, MX, TXT, NS, SRV, CAA)
- name: varchar(255)
- content: text
- ttl: int DEFAULT 3600
- priority: int NULL (for MX/SRV)
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX (domain_id, type)

**Foreign Keys:**
- domain_id → domains(id) ON DELETE CASCADE

---

#### **registrars**
Registrar API configuration.

```sql
- id: bigint PK
- name: varchar(255) UNIQUE
- type: varchar(50) (resellerclub, mock, etc.)
- config: json (API endpoints, settings)
- credentials: json (encrypted API keys)
- is_active: boolean DEFAULT true
- created_at, updated_at, deleted_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (name)
- INDEX (type)

---

#### **tlds**
Top-level domain configurations per registrar.

```sql
- id: bigint PK
- registrar_id: bigint FK
- extension: varchar(50) (com, net, org, etc.)
- min_years: tinyint DEFAULT 1
- max_years: tinyint DEFAULT 10
- supports_dns: boolean DEFAULT true
- supports_whois_privacy: boolean DEFAULT true
- is_active: boolean DEFAULT true
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (registrar_id, extension)
- INDEX (extension)

**Foreign Keys:**
- registrar_id → registrars(id) ON DELETE CASCADE

---

#### **tld_prices**
Base pricing for TLDs from registrars.

```sql
- id: bigint PK
- registrar_id: bigint FK
- tld: varchar(50)
- action: enum(register, renew, transfer)
- years: tinyint
- price: decimal(10,2)
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX (registrar_id, tld, action)

**Foreign Keys:**
- registrar_id → registrars(id) ON DELETE CASCADE

---

#### **partner_pricing_rules**
Custom pricing markup rules per partner.

```sql
- id: bigint PK
- partner_id: bigint FK
- tld: varchar(50)
- action: enum(register, renew, transfer)
- markup_type: enum(fixed, percentage)
- markup_value: decimal(10,2)
- is_active: boolean DEFAULT true
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX (partner_id, tld)

**Foreign Keys:**
- partner_id → partners(id) ON DELETE CASCADE

---

#### **wallets**
Partner wallet/balance management.

```sql
- id: bigint PK
- partner_id: bigint FK UNIQUE
- balance: decimal(10,2) DEFAULT 0.00
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (partner_id)

**Foreign Keys:**
- partner_id → partners(id) ON DELETE CASCADE

---

#### **wallet_transactions**
Transaction ledger for wallet operations.

```sql
- id: bigint PK
- wallet_id: bigint FK
- partner_id: bigint FK
- type: enum(credit, debit, refund, adjustment)
- amount: decimal(10,2)
- description: text
- reference_type: varchar(255) NULL (polymorphic)
- reference_id: bigint NULL (polymorphic)
- created_by: bigint FK (users) NULL
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX (wallet_id)
- INDEX (partner_id)
- INDEX (type)
- INDEX (reference_type, reference_id)
- INDEX (created_at)

**Foreign Keys:**
- wallet_id → wallets(id) ON DELETE CASCADE
- partner_id → partners(id) ON DELETE CASCADE
- created_by → users(id) ON DELETE SET NULL

---

#### **invoices**
Invoice management for domain operations.

```sql
- id: bigint PK
- invoice_number: varchar(50) UNIQUE
- partner_id: bigint FK
- client_id: bigint FK (users)
- status: enum(draft, pending, paid, cancelled, refunded)
- subtotal: decimal(10,2)
- tax_amount: decimal(10,2) DEFAULT 0.00
- total: decimal(10,2)
- due_date: date NULL
- paid_at: timestamp NULL
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (invoice_number)
- INDEX (partner_id, status)
- INDEX (client_id)

**Foreign Keys:**
- partner_id → partners(id) ON DELETE CASCADE
- client_id → users(id) ON DELETE CASCADE

---

#### **invoice_items**
Line items for invoices.

```sql
- id: bigint PK
- invoice_id: bigint FK
- description: varchar(255)
- quantity: int DEFAULT 1
- unit_price: decimal(10,2)
- total: decimal(10,2)
- item_type: varchar(100) NULL (polymorphic)
- item_id: bigint NULL (polymorphic)
- created_at, updated_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX (invoice_id)
- INDEX (item_type, item_id)

**Foreign Keys:**
- invoice_id → invoices(id) ON DELETE CASCADE

---

#### **audit_logs**
Comprehensive audit trail for security and compliance.

```sql
- id: bigint PK
- user_id: bigint FK NULL
- partner_id: bigint FK NULL
- action: varchar(50)
- auditable_type: varchar(255) (polymorphic)
- auditable_id: bigint (polymorphic)
- old_values: json NULL
- new_values: json NULL
- ip_address: varchar(45) NULL
- user_agent: text NULL
- created_at: timestamp
```

**Indexes:**
- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (partner_id)
- INDEX (auditable_type, auditable_id)
- INDEX (action)
- INDEX (created_at)

**Foreign Keys:**
- user_id → users(id) ON DELETE SET NULL
- partner_id → partners(id) ON DELETE SET NULL

---

### 2.2 Database Relationships Summary

**One-to-Many:**
- Partner → Users (clients)
- Partner → Domains
- Partner → Invoices
- Partner → PricingRules
- User → Domains (as client)
- Domain → Contacts
- Domain → Nameservers
- Domain → DnsRecords
- Domain → Documents
- Invoice → InvoiceItems
- Registrar → Tlds

**One-to-One:**
- Partner → PartnerBranding
- Partner → Wallet

**Polymorphic:**
- AuditLog (auditable_type, auditable_id)
- WalletTransaction (reference_type, reference_id)
- InvoiceItem (item_type, item_id)

---

## 3. Service Layer Patterns

### 3.1 Service Architecture

DomainDesk follows a service-oriented architecture pattern where business logic is encapsulated in dedicated service classes, separating concerns from controllers and models.

### 3.2 Core Services

#### **DomainRegistrationService**
**Location:** `app/Services/DomainRegistrationService.php`

Handles domain registration workflows including:
- Availability checking
- Price calculation
- Registration request to registrar
- Domain record creation
- Job dispatching for async processing
- Invoice generation

**Key Methods:**
```php
register(string $domain, User $client, array $contacts, int $years)
calculatePrice(string $domain, int $years): decimal
validateAvailability(string $domain): bool
```

---

#### **DomainRenewalService**
**Location:** `app/Services/DomainRenewalService.php`

Manages domain renewal operations:
- Renewal request to registrar
- Expiry date updates
- Wallet deduction
- Invoice creation
- Auto-renewal processing

**Key Methods:**
```php
renew(Domain $domain, int $years)
processAutoRenewals()
calculateRenewalPrice(Domain $domain, int $years)
```

---

#### **DomainTransferService**
**Location:** `app/Services/DomainTransferService.php`

Handles domain transfers:
- Transfer initiation
- Auth code validation
- Transfer status tracking
- Completion processing

**Key Methods:**
```php
initiateTransfer(string $domain, string $authCode, User $client)
checkTransferStatus(Domain $domain)
completeTransfer(Domain $domain)
cancelTransfer(Domain $domain)
```

---

#### **DomainSearchService**
**Location:** `app/Services/DomainSearchService.php`

Provides domain search functionality:
- Multi-domain availability checking
- Results caching (5-minute TTL)
- TLD suggestion generation
- Price calculation for results

**Key Methods:**
```php
searchMultiple(array $domains, bool $useCache = true)
search(string $domain, bool $useCache = true)
suggestAlternatives(string $domain)
```

---

#### **PartnerContextService**
**Location:** `app/Services/PartnerContextService.php`

Manages the current partner context throughout the request lifecycle:
- Domain-based partner resolution
- Partner data caching (5-minute TTL)
- Branding and wallet preloading
- Fallback to default partner

**Key Methods:**
```php
resolveFromDomain(string $domain): ?Partner
resolveFromRequest(): ?Partner
setPartner(?Partner $partner): void
getPartner(): ?Partner
getBranding(): ?PartnerBranding
getWallet(): ?Wallet
getPricingService(): PricingService
```

**Resolution Flow:**
1. Check if already resolved (singleton pattern)
2. Extract domain from request host
3. Query partner_domains table with cache
4. Preload branding and wallet relations
5. Fallback to default partner if configured

---

#### **PricingService**
**Location:** `app/Services/PricingService.php`

Handles all pricing calculations:
- Base registrar prices
- Partner-specific markup rules
- Dynamic pricing by action type
- Bulk pricing calculations

**Key Methods:**
```php
getPrice(string $tld, string $action, int $years)
calculateCustomerPrice(string $tld, string $action, int $years)
applyMarkup(decimal $basePrice, PartnerPricingRule $rule)
```

---

#### **DnsService**
**Location:** `app/Services/DnsService.php`

DNS record management:
- Record creation/update/deletion
- Zone validation
- Registrar DNS API integration
- TTL management

---

#### **RegistrarSyncService**
**Location:** `app/Services/RegistrarSyncService.php`

Synchronizes domain data with registrars:
- Status updates
- Expiry date syncing
- Contact information updates
- Rate-limited batch operations

**Key Methods:**
```php
syncDomain(Domain $domain)
syncBatch(Collection $domains)
syncExpiringSoon(int $days = 30)
```

---

### 3.3 Registrar Abstraction Layer

#### **AbstractRegistrar**
**Location:** `app/Services/Registrar/AbstractRegistrar.php`

Base class implementing `RegistrarInterface` with common functionality:

**Features:**
- API call logging with sanitization
- Rate limiting (60 req/min default)
- Response standardization
- Error handling and wrapping
- Cache helpers
- Domain validation

**Protected Methods:**
```php
executeApiCall(string $method, callable $callback, array $params)
checkRateLimit(string $method)
sanitizeLogParams(array $params)
createResponse(bool $success, mixed $data, string $message, array $errors)
cacheOrExecute(string $key, int $ttl, callable $callback)
validateDomain(string $domain)
validateRequired(array $data, array $required)
```

---

#### **RegistrarInterface**
**Location:** `app/Contracts/RegistrarInterface.php`

Defines the contract all registrars must implement:

**Required Methods:**
```php
checkAvailability(string $domain): array
registerDomain(string $domain, array $contacts, int $years): array
renewDomain(string $domain, int $years): array
getDomainInfo(string $domain): array
updateNameservers(string $domain, array $nameservers): array
getAuthCode(string $domain): array
initiateTransfer(string $domain, string $authCode): array
updateDnsRecords(string $domain, array $records): array
getDnsRecords(string $domain): array
testConnection(): bool
```

---

#### **ResellerClubRegistrar**
**Location:** `app/Services/Registrar/ResellerClubRegistrar.php`

Production registrar integration with ResellerClub API.

**Features:**
- HTTP API client with Guzzle
- JSON response parsing
- Error code mapping
- Order ID tracking
- Customer ID management

---

#### **MockRegistrar**
**Location:** `app/Services/Registrar/MockRegistrar.php`

In-memory mock for development and testing.

**Features:**
- Cache-based state storage
- Configurable responses
- Request history tracking
- Domain state simulation
- Transfer workflow simulation

---

#### **RegistrarFactory**
**Location:** `app/Services/Registrar/RegistrarFactory.php`

Factory pattern for creating registrar instances:

```php
public static function make(Registrar $registrar): RegistrarInterface
{
    return match($registrar->type) {
        'resellerclub' => new ResellerClubRegistrar($registrar->config, $registrar->credentials),
        'mock' => new MockRegistrar($registrar->config, $registrar->credentials),
        default => throw new \InvalidArgumentException("Unknown registrar type: {$registrar->type}")
    };
}
```

---

### 3.4 Service Layer Best Practices

1. **Single Responsibility**: Each service focuses on one domain area
2. **Dependency Injection**: Services receive dependencies via constructor
3. **Transaction Management**: Database operations wrapped in transactions
4. **Exception Handling**: Throw typed exceptions (e.g., `RegistrarException`)
5. **Job Dispatching**: Long operations delegated to queue jobs
6. **Logging**: Structured logging with context data
7. **Testing**: Services are unit-testable with mocked dependencies

---

## 4. Multi-Tenancy Design

DomainDesk implements a **single-database multi-tenancy** architecture where all partners share the same database with strict data isolation enforced at the application layer.

### 4.1 Partner Context Resolution

#### **PartnerContextMiddleware**
**Location:** `app/Http/Middleware/PartnerContextMiddleware.php`

Executes on every request to establish partner context:

```php
public function handle(Request $request, Closure $next): Response
{
    // Skip admin routes
    if ($request->is('admin/*')) {
        return $next($request);
    }

    // Resolve partner from request domain
    $partner = $this->partnerContext->resolveWithFallback();

    if (!$partner && !config('partner.allow_missing_partner', false)) {
        abort(404, 'Partner not found for this domain.');
    }

    return $next($request);
}
```

**Resolution Strategy:**
1. Extract hostname from request
2. Query `partner_domains` table for matching domain
3. Load partner with branding and wallet
4. Store in `PartnerContextService` singleton
5. Fallback to default partner if configured

---

### 4.2 BelongsToPartner Trait

**Location:** `app/Models/Concerns/BelongsToPartner.php`

Applied to all partner-scoped models (Domain, Invoice, User, etc.):

**Key Features:**

1. **Global Scope Application**:
```php
protected static function bootBelongsToPartner(): void
{
    static::addGlobalScope(new PartnerScope());
}
```

2. **Automatic partner_id Assignment**:
```php
static::creating(function ($model) {
    if (empty($model->partner_id)) {
        $model->partner_id = static::determinePartnerId($model);
    }
    // Validation: partner_id required
    if (empty($model->partner_id) && !static::allowsNullPartnerId($model)) {
        throw new \InvalidArgumentException('partner_id is required');
    }
});
```

3. **Immutability Protection**:
```php
static::updating(function ($model) {
    if ($model->isDirty('partner_id')) {
        throw new \Exception('partner_id cannot be changed after creation for security reasons');
    }
});
```

4. **Helper Methods**:
```php
public function belongsToPartner(int|Partner $partner): bool
public function belongsToCurrentPartner(): bool
public function scopeForPartner(Builder $query, int|Partner $partner)
public function scopeWithoutPartnerScope(Builder $query)
```

---

### 4.3 PartnerScope Global Scope

**Location:** `app/Scopes/PartnerScope.php`

Automatically filters all queries by partner_id:

**Implementation:**
```php
public function apply(Builder $builder, Model $model): void
{
    // Skip for SuperAdmin users
    if ($this->isSuperAdmin()) {
        return;
    }

    // Get partner ID from context or authenticated user
    $partnerId = $this->getCurrentPartnerId();

    if ($partnerId !== null) {
        $builder->where($model->getTable() . '.partner_id', $partnerId);
    }
}
```

**Special Cases:**
- **SuperAdmin**: Scope not applied (sees all data)
- **User Model**: Only filters client role users
- **Explicit Bypass**: Use `->withoutPartnerScope()` or `->forPartner($id)`

---

### 4.4 Data Isolation Guarantees

**Database Level:**
- All partner-scoped tables have `partner_id` foreign key
- Foreign keys enforce referential integrity
- Indexes on `(partner_id, ...)` for query performance

**Application Level:**
- Global scope automatically applied to all queries
- `partner_id` auto-assigned on create
- `partner_id` immutable after creation
- Explicit bypass required for cross-partner operations

**Request Level:**
- Middleware establishes partner context before routing
- Context stored in singleton service
- Audit logs track partner context
- Tests can manually set partner context

---

### 4.5 Partner Data Model

**Partner-Specific Data:**
- Branding (logo, colors, email templates)
- Custom domain mapping
- Pricing rules and markup
- Wallet balance
- Users (Partner and Client roles)
- Domains owned by clients

**Shared Data:**
- Registrar configurations
- Base TLD prices
- System settings (SuperAdmin only)

---

### 4.6 Multi-Tenancy Security

**Threat Protection:**
1. **Horizontal Privilege Escalation**: Prevented by global scope + immutable partner_id
2. **SQL Injection**: Eloquent ORM with parameterized queries
3. **Direct Object Reference**: Partner scope validates ownership
4. **Session Hijacking**: Laravel session security + partner context validation

**Testing Isolation:**
- `RefreshDatabase` trait clears all tenant data
- `PartnerContextService::reset()` for test isolation
- Factory seeders respect partner_id

---

## 5. Security Architecture

### 5.1 Authentication

**Implementation:** Laravel Sanctum (API token-based)

**User Roles:**
- `SuperAdmin`: Full system access, no partner_id
- `Partner`: Partner-specific admin access
- `Client`: End-user access, scoped to partner

**Password Security:**
- Bcrypt hashing (Laravel default)
- Password validation rules
- Password reset via email

---

### 5.2 Authorization

**Role-Based Access Control (RBAC):**

**Permission Matrix:**
```
Resource              SuperAdmin    Partner      Client
------------------------------------------------------------
Partners              CRUD          Read (own)   None
Users                 CRUD          CRUD (own)   Read (own)
Domains               CRUD (all)    CRUD (own)   CRUD (own)
Registrars            CRUD          Read         None
Pricing               CRUD          CRUD (own)   Read
Invoices              Read (all)    Read (own)   Read (own)
Settings              CRUD          Read         None
Audit Logs            Read (all)    Read (own)   None
```

**Policy Implementation:**
```php
// Example: DomainPolicy
public function update(User $user, Domain $domain): bool
{
    if ($user->isSuperAdmin()) {
        return true;
    }
    
    return $domain->partner_id === $user->partner_id 
        && ($user->isPartner() || $domain->client_id === $user->id);
}
```

---

### 5.3 Data Encryption

**Encrypted Fields:**
- `domains.auth_code`: Laravel's `encrypt()/decrypt()`
- `registrars.credentials`: JSON encrypted in database
- Session data: Encrypted by default

**Encryption at Rest:**
- Database-level encryption (MySQL/MariaDB)
- Filesystem encryption for storage

**Encryption in Transit:**
- HTTPS/TLS for all web traffic
- API communication over HTTPS
- Registrar API calls over HTTPS

---

### 5.4 Audit Logging

**Auditable Trait** (`app/Models/Concerns/Auditable.php`):

Automatically tracks:
- Model creation, updates, deletion
- User responsible for action
- IP address and user agent
- Old and new values (JSON)
- Partner context

**Audit Log Storage:**
```php
AuditLog::create([
    'user_id' => Auth::id(),
    'partner_id' => $model->partner_id,
    'action' => 'updated',
    'auditable_type' => Domain::class,
    'auditable_id' => $domain->id,
    'old_values' => ['status' => 'pending'],
    'new_values' => ['status' => 'active'],
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

**Use Cases:**
- Security incident investigation
- Compliance reporting
- User activity tracking
- Data change history

---

### 5.5 Input Validation & Sanitization

**Request Validation:**
- Form Request classes for complex validation
- Validation rules at controller level
- Type hinting with strict types

**SQL Injection Prevention:**
- Eloquent ORM (prepared statements)
- Query builder with parameter binding
- No raw SQL queries without bindings

**XSS Prevention:**
- Blade templating auto-escapes output
- `htmlspecialchars()` for raw output
- Content Security Policy headers

---

### 5.6 Rate Limiting

**Application Level:**
- Throttle middleware on routes
- Default: 60 requests/minute per user

**Registrar API Level:**
- AbstractRegistrar implements rate limiting
- Default: 60 requests/minute per registrar
- Uses Laravel RateLimiter facade

---

### 5.7 Sensitive Data Handling

**Credentials Storage:**
- `.env` file (not in version control)
- Registrar API credentials encrypted in database
- Never logged or exposed in responses

**Logging Sanitization:**
```php
protected function sanitizeLogParams(array $params): array
{
    $sensitiveKeys = ['password', 'api_key', 'auth_code', 'token'];
    // Recursively replace with '***REDACTED***'
}
```

---

## 6. Performance Considerations

### 6.1 Caching Strategies

**Partner Context Cache:**
```php
Cache::remember("partner:domain:{$domain}", 300, function() {
    return Partner::whereHas('domains', ...)->first();
});
```
- **TTL:** 5 minutes
- **Invalidation:** On partner domain changes

**Domain Search Cache:**
```php
Cache::remember("domain:search:{$domain}", 300, function() {
    return $registrar->checkAvailability($domain);
});
```
- **TTL:** 5 minutes (domain availability)
- **Invalidation:** Manual or time-based

**Registrar Response Cache:**
- Used in AbstractRegistrar::cacheOrExecute()
- Configurable TTL per operation
- Key: `registrar:{name}:cache:{key}`

**Query Result Cache:**
- Pricing data cached after calculation
- TLD list cached per registrar
- Config/settings cached indefinitely with invalidation

---

### 6.2 Queue Jobs

**Asynchronous Operations** (`app/Jobs/`):

1. **ProcessDomainRegistrationJob**: 
   - Calls registrar API
   - Updates domain status
   - Generates invoice

2. **ProcessDomainRenewalJob**:
   - Processes auto-renewals
   - Wallet deductions
   - Sends notifications

3. **SyncDomainJob**:
   - Syncs domain status with registrar
   - Updates expiry dates
   - Rate-limited batch processing

4. **Email Jobs**:
   - SendDomainRegistrationEmail
   - SendRenewalEmailJob
   - SendDomainTransferEmailJob

**Queue Configuration:**
- Driver: Redis (recommended) or Database
- Workers: Scaled based on load
- Retry logic: 3 attempts with exponential backoff

---

### 6.3 Database Optimization

**Indexing Strategy:**
```sql
-- Partner-scoped queries
INDEX (partner_id, status)
INDEX (partner_id, created_at)

-- Date range queries
INDEX (expires_at, status)
INDEX (created_at)

-- Lookup queries
UNIQUE (name)
UNIQUE (email)

-- Polymorphic relationships
INDEX (auditable_type, auditable_id)
INDEX (reference_type, reference_id)

-- Join optimization
INDEX (domain_id)
INDEX (client_id)
INDEX (wallet_id)
```

**Query Optimization:**
- Eager loading: `->with(['contacts', 'nameservers'])`
- Lazy eager loading: `->load('contacts')`
- Chunk processing: `->chunk(100, function($domains) {...})`
- Select specific columns: `->select(['id', 'name', 'status'])`

**Database Connection Pool:**
- Use persistent connections
- Configure max connections based on expected load
- Monitor connection usage

---

### 6.4 Pagination

**Default Pagination:**
- 15 items per page for listings
- 50 items per page for API endpoints

**Cursor Pagination:**
- For large datasets (audit logs, transactions)
- More efficient than offset pagination

---

### 6.5 N+1 Query Prevention

**Eager Loading Examples:**
```php
// Domains with contacts and partner
Domain::with(['contacts', 'partner.branding'])->get();

// Users with domain count
User::withCount('domains')->whereClient()->get();

// Invoices with items and domain references
Invoice::with('items.item')->where('partner_id', $partnerId)->get();
```

**Laravel Debugbar/Telescope:**
- Monitor query count in development
- Identify N+1 issues early

---

### 6.6 Horizontal Scaling

**Stateless Application:**
- Session stored in Redis/Database (not filesystem)
- Partner context resolved per request
- No server-side state dependencies

**Load Balancing:**
- Multiple app servers behind load balancer
- Sticky sessions not required
- Shared cache (Redis) and database

**Database Scaling:**
- Master-slave replication for read scaling
- Read/write splitting (Laravel supports)
- Database sharding by partner_id (future consideration)

**Queue Scaling:**
- Multiple queue workers on separate servers
- Supervisor for worker management
- Horizontal worker scaling based on queue depth

---

### 6.7 Performance Monitoring

**Application Performance Monitoring (APM):**
- Laravel Telescope for local development
- New Relic / Datadog for production
- Query logging with slow query alerts

**Key Metrics:**
- Request response time (target: <200ms)
- Database query time (target: <50ms)
- Registrar API response time
- Queue job processing time
- Cache hit ratio (target: >80%)

**Bottleneck Identification:**
- Slow registrar API calls → Use cache + async jobs
- Database queries → Add indexes, optimize queries
- High memory usage → Optimize eager loading, chunk large datasets

---

## Conclusion

DomainDesk's architecture is designed for scalability, security, and multi-tenancy. Key strengths:

- **Clean Separation of Concerns**: Models, services, controllers, and jobs
- **Registrar Abstraction**: Easy to add new registrar integrations
- **Robust Multi-Tenancy**: Partner context + global scopes + immutable tenant IDs
- **Security First**: Encryption, audit logging, RBAC, and input validation
- **Performance Optimized**: Caching, indexing, queue jobs, and eager loading

For additional documentation, see:
- [QUICK_START_GUIDE.md](QUICK_START_GUIDE.md)
- [RESELLERCLUB_QUICK_START.md](RESELLERCLUB_QUICK_START.md)
- [PHASE_8.4_SYSTEM_ADMIN_GUIDE.md](PHASE_8.4_SYSTEM_ADMIN_GUIDE.md)
- [PARTNER_CONTEXT_DOCS.md](PARTNER_CONTEXT_DOCS.md)
