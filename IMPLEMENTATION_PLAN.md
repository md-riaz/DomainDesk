# DomainDesk - Implementation Plan

> **Product Name**: DomainDesk  
> **Product Type**: White-Label Domain Reseller & Client Billing Platform  
> **Tech Stack**: Laravel 12 + Livewire 4 + SQLite (dev) / PostgreSQL (prod)

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Implementation Phases](#implementation-phases)
3. [Phase Details](#phase-details)
4. [Technical Architecture](#technical-architecture)
5. [Deployment Strategy](#deployment-strategy)

---

## Project Overview

### Purpose
Build a SaaS platform where:
- **Partners** (resellers) sell domains under their own brand
- **Clients** (end-users) manage domains & invoices
- **Admin** controls registrar integration, pricing, and compliance

### Core Features
- ✅ Multi-tenant architecture with complete data isolation
- ✅ White-label branding (custom domains, logos, colors, emails)
- ✅ Domain lifecycle management (register, renew, transfer, manage)
- ✅ Wallet-based billing system with append-only ledger
- ✅ Partner-specific pricing rules and markup
- ✅ Automated renewals and expiry notifications
- ✅ Full audit trail for compliance
- ✅ Registrar-agnostic design

### Non-Functional Requirements
- Multi-tenant safe (hard isolation)
- Registrar-agnostic (abstraction layer)
- Scales to 100k+ domains
- Deterministic billing (no rounding errors)
- White-label first (not bolted on)
- Security-first design

---

## Implementation Phases

### Phase 0: Project Foundation ✅
**Status**: Complete  
**PR**: Initial setup  
**Duration**: 1 day

- [x] Laravel 12 + Livewire 4 installed
- [x] SQLite database configured
- [x] Documentation files created
- [x] Repository structure established
- [x] Implementation plan document created

---

### Phase 1: Database Schema & Core Models
**Status**: Not Started  
**Estimated PRs**: 3-4  
**Duration**: 1 week

#### PR 1.1: Core Tables - Users & Roles
**Files to Create/Modify:**
```
database/migrations/
  - 2024_01_xx_create_roles_table.php
  - 2024_01_xx_add_role_fields_to_users_table.php
app/Models/
  - Role.php (enum or model)
```

**Checklist:**
- [ ] Create roles system (Super Admin, Partner, Client)
- [ ] Extend users table with role and partner relationship
- [ ] Add soft deletes to users
- [ ] Create User model with role scopes
- [ ] Add authentication guards for each role
- [ ] Create seeders for default roles

#### PR 1.2: Partner & Branding System
**Files to Create/Modify:**
```
database/migrations/
  - 2024_01_xx_create_partners_table.php
  - 2024_01_xx_create_partner_domains_table.php
  - 2024_01_xx_create_partner_branding_table.php
app/Models/
  - Partner.php
  - PartnerDomain.php
  - PartnerBranding.php
```

**Checklist:**
- [ ] Create partners table (name, email, status, is_active)
- [ ] Create partner_domains table (domain, is_primary, is_verified, dns_status)
- [ ] Create partner_branding table (logo, favicon, colors, email_sender)
- [ ] Define relationships (partner has many domains, has one branding)
- [ ] Add partner_id foreign key constraints
- [ ] Create factory and seeders for development

#### PR 1.3: Domain Management Tables
**Files to Create/Modify:**
```
database/migrations/
  - 2024_01_xx_create_domains_table.php
  - 2024_01_xx_create_domain_contacts_table.php
  - 2024_01_xx_create_domain_nameservers_table.php
  - 2024_01_xx_create_domain_dns_records_table.php
app/Models/
  - Domain.php
  - DomainContact.php
  - DomainNameserver.php
  - DomainDnsRecord.php
```

**Checklist:**
- [ ] Create domains table (name, status, client_id, partner_id, registrar_id, expires_at, auto_renew)
- [ ] Create domain_contacts table (type: registrant/admin/tech/billing, contact details)
- [ ] Create domain_nameservers table (nameserver, order)
- [ ] Create domain_dns_records table (type, name, value, ttl, priority)
- [ ] Add domain status enum (pending, active, expired, grace_period, redemption, suspended, transferred_out)
- [ ] Define relationships and scopes
- [ ] Create domain factories

#### PR 1.4: Billing & Wallet System
**Files to Create/Modify:**
```
database/migrations/
  - 2024_01_xx_create_wallets_table.php
  - 2024_01_xx_create_wallet_transactions_table.php
  - 2024_01_xx_create_invoices_table.php
  - 2024_01_xx_create_invoice_items_table.php
app/Models/
  - Wallet.php
  - WalletTransaction.php
  - Invoice.php
  - InvoiceItem.php
```

**Checklist:**
- [ ] Create wallets table (partner_id, balance is computed)
- [ ] Create wallet_transactions table (append-only: type, amount, description, reference_type, reference_id)
- [ ] Create invoices table (invoice_number, partner_id, client_id, status, total, issued_at, paid_at)
- [ ] Create invoice_items table (invoice_id, description, quantity, unit_price, total)
- [ ] Add transaction types enum (credit, debit, refund, adjustment)
- [ ] Add invoice status enum (draft, issued, paid, failed, refunded)
- [ ] Ensure immutability constraints
- [ ] Create wallet balance calculation method

#### PR 1.5: Registrar & Pricing Tables
**Files to Create/Modify:**
```
database/migrations/
  - 2024_01_xx_create_registrars_table.php
  - 2024_01_xx_create_tlds_table.php
  - 2024_01_xx_create_tld_prices_table.php
  - 2024_01_xx_create_partner_pricing_rules_table.php
app/Models/
  - Registrar.php
  - Tld.php
  - TldPrice.php
  - PartnerPricingRule.php
```

**Checklist:**
- [ ] Create registrars table (name, api_class, credentials, is_active)
- [ ] Create tlds table (extension, registrar_id, min_years, max_years, features)
- [ ] Create tld_prices table (tld_id, action: register/renew/transfer, years, price, effective_date)
- [ ] Create partner_pricing_rules table (partner_id, tld_id, markup_type, markup_value, duration)
- [ ] Add pricing calculation logic
- [ ] Create historical price tracking

#### PR 1.6: Audit & Compliance Tables
**Files to Create/Modify:**
```
database/migrations/
  - 2024_01_xx_create_audit_logs_table.php
  - 2024_01_xx_create_domain_documents_table.php
app/Models/
  - AuditLog.php
  - DomainDocument.php
```

**Checklist:**
- [ ] Create audit_logs table (user_id, partner_id, action, model_type, model_id, old_values, new_values, ip_address, user_agent)
- [ ] Create domain_documents table (domain_id, document_type, file_path, verified_at)
- [ ] Add audit logging trait
- [ ] Implement automatic audit logging on model events

---

### Phase 2: Authentication & Multi-Tenancy
**Status**: Not Started  
**Estimated PRs**: 2-3  
**Duration**: 4-5 days

#### PR 2.1: Authentication System
**Files to Create/Modify:**
```
app/Http/Controllers/Auth/
  - LoginController.php
  - RegisterController.php
  - LogoutController.php
app/Http/Middleware/
  - RoleMiddleware.php
  - PartnerContextMiddleware.php
routes/
  - web.php (auth routes)
resources/views/auth/
  - login.blade.php
  - register.blade.php
```

**Checklist:**
- [ ] Implement email/password authentication
- [ ] Create role-based middleware (admin, partner, client)
- [ ] Add login/logout functionality
- [ ] Create registration flow (clients only)
- [ ] Add remember me functionality
- [ ] Implement login throttling
- [ ] Create branded login pages

#### PR 2.2: Partner Context Resolution
**Files to Create/Modify:**
```
app/Http/Middleware/
  - PartnerContextMiddleware.php
app/Services/
  - PartnerContextService.php
app/Providers/
  - AppServiceProvider.php (register context service)
```

**Checklist:**
- [ ] Create partner context service
- [ ] Implement domain → partner resolution
- [ ] Load partner branding on every request
- [ ] Load partner pricing rules
- [ ] Create partner context helper functions
- [ ] Add context to Livewire components
- [ ] Handle multi-domain support

#### PR 2.3: Tenant Isolation
**Files to Create/Modify:**
```
app/Models/Concerns/
  - BelongsToPartner.php (trait)
app/Scopes/
  - PartnerScope.php
tests/Feature/
  - TenantIsolationTest.php
```

**Checklist:**
- [ ] Create BelongsToPartner trait
- [ ] Implement global scopes for tenant isolation
- [ ] Add partner_id to all relevant queries
- [ ] Create helper methods for cross-partner checks
- [ ] Test tenant isolation thoroughly
- [ ] Add security tests for data leakage

---

### Phase 3: Registrar Integration Layer
**Status**: In Progress (3/4 completed) ✅  
**Estimated PRs**: 3-4  
**Duration**: 1 week

#### PR 3.1: Registrar Interface Contract ✅
**Status**: Complete  
**Files to Create/Modify:**
```
app/Contracts/
  - RegistrarInterface.php
app/Services/Registrar/
  - AbstractRegistrar.php
  - RegistrarFactory.php
app/Exceptions/
  - RegistrarException.php
```

**Checklist:**
- [x] Define RegistrarInterface methods (register, renew, transfer, updateNameservers, etc.)
- [x] Create AbstractRegistrar base class
- [x] Implement RegistrarFactory for dynamic loading
- [x] Create custom exceptions for registrar errors
- [x] Define standard response format
- [x] Add logging for all registrar calls
- [x] Create registrar configuration system

#### PR 3.2: Mock Registrar Implementation ✅
**Status**: Complete  
**Files to Create/Modify:**
```
app/Services/Registrar/
  - MockRegistrar.php
tests/Feature/Registrar/
  - MockRegistrarTest.php
```

**Checklist:**
- [x] Create MockRegistrar for testing
- [x] Implement all interface methods with fake responses
- [x] Add configurable delays and failures
- [x] Create test suite for registrar operations
- [x] Add mock data generators
- [x] Document mock registrar usage

#### PR 3.3: ResellerClub/LogicBoxes Integration ✅
**Status**: Complete  
**Files to Create/Modify:**
```
app/Services/Registrar/
  - ResellerClubRegistrar.php
tests/Feature/Registrar/
  - ResellerClubRegistrarTest.php
config/
  - registrars.php (updated)
docs/
  - PHASE_3.3_RESELLERCLUB_INTEGRATION.md
  - RESELLERCLUB_QUICK_START.md
```

**Checklist:**
- [x] Implement ResellerClub API client
- [x] Map API responses to standard format
- [x] Handle API errors gracefully
- [x] Implement rate limiting
- [x] Add API credential validation
- [x] Create comprehensive test suite (36 tests)
- [x] Add complete documentation
- [x] Support test mode (sandbox) and production
- [x] Cache responses appropriately
- [x] LogicBoxes compatibility

#### PR 3.4: Registrar Sync Service
**Files to Create/Modify:**
```
app/Services/
  - RegistrarSyncService.php
app/Console/Commands/
  - SyncDomainStatus.php
  - SyncTldPrices.php
```

**Checklist:**
- [ ] Create domain status sync service
- [ ] Implement TLD pricing sync
- [ ] Add batch processing for large datasets
- [ ] Create Artisan commands for manual sync
- [ ] Add conflict resolution logic
- [ ] Log all sync operations

---

### Phase 4: Domain Operations
**Status**: Not Started  
**Estimated PRs**: 4-5  
**Duration**: 1.5 weeks

#### PR 4.1: Domain Search & Availability
**Files to Create/Modify:**
```
app/Services/
  - DomainSearchService.php
app/Livewire/Domain/
  - SearchDomain.php
resources/views/livewire/domain/
  - search-domain.blade.php
```

**Checklist:**
- [ ] Create domain search service
- [ ] Implement availability check via registrar
- [ ] Add bulk domain search
- [ ] Show pricing for available domains
- [ ] Create search UI component
- [ ] Add search history
- [ ] Implement domain suggestions

#### PR 4.2: Domain Registration Flow
**Files to Create/Modify:**
```
app/Services/
  - DomainRegistrationService.php
app/Livewire/Domain/
  - RegisterDomain.php
resources/views/livewire/domain/
  - register-domain.blade.php
```

**Checklist:**
- [ ] Create registration service with transaction handling
- [ ] Generate invoice for registration
- [ ] Debit partner wallet
- [ ] Call registrar API
- [ ] Handle registration failures with rollback
- [ ] Create domain record
- [ ] Send confirmation email
- [ ] Create comprehensive audit log
- [ ] Add UI for registration flow

#### PR 4.3: Domain Renewal System
**Files to Create/Modify:**
```
app/Services/
  - DomainRenewalService.php
app/Console/Commands/
  - ProcessAutoRenewals.php
app/Livewire/Domain/
  - RenewDomain.php
```

**Checklist:**
- [ ] Create manual renewal service
- [ ] Implement auto-renewal logic
- [ ] Create cron job for auto-renewals
- [ ] Check wallet balance before renewal
- [ ] Generate renewal invoices
- [ ] Call registrar renewal API
- [ ] Update domain expiry dates
- [ ] Handle renewal failures gracefully
- [ ] Send renewal confirmation emails

#### PR 4.4: Domain Transfer System
**Files to Create/Modify:**
```
app/Services/
  - DomainTransferService.php
app/Livewire/Domain/
  - TransferDomain.php
resources/views/livewire/domain/
  - transfer-domain.blade.php
```

**Checklist:**
- [ ] Create transfer-in service
- [ ] Validate auth code
- [ ] Create transfer invoice
- [ ] Initiate registrar transfer
- [ ] Track transfer status
- [ ] Handle transfer completion
- [ ] Send transfer notifications
- [ ] Create transfer-out functionality

#### PR 4.5: Nameserver & DNS Management
**Files to Create/Modify:**
```
app/Services/
  - NameserverService.php
  - DnsService.php
app/Livewire/Domain/
  - ManageNameservers.php
  - ManageDns.php
resources/views/livewire/domain/
  - manage-nameservers.blade.php
  - manage-dns.blade.php
```

**Checklist:**
- [ ] Create nameserver update service (min 2, max 4)
- [ ] Validate nameserver format
- [ ] Update via registrar API
- [ ] Create DNS record management (A, AAAA, CNAME, MX, TXT)
- [ ] Add TTL support
- [ ] Implement registrar-specific DNS features
- [ ] Create UI for nameserver management
- [ ] Create UI for DNS management
- [ ] Add validation and error handling

---

### Phase 5: Pricing Engine & Wallet System
**Status**: Not Started  
**Estimated PRs**: 3-4  
**Duration**: 1 week

#### PR 5.1: Pricing Calculation Engine
**Files to Create/Modify:**
```
app/Services/
  - PricingEngine.php
tests/Unit/
  - PricingEngineTest.php
```

**Checklist:**
- [ ] Implement base price fetching
- [ ] Apply partner markup rules (fixed/percentage)
- [ ] Handle promo overrides
- [ ] Calculate multi-year pricing
- [ ] Ensure deterministic calculations (no floating point errors)
- [ ] Create pricing calculator helper
- [ ] Add comprehensive unit tests
- [ ] Document pricing logic

#### PR 5.2: Wallet Transaction System
**Files to Create/Modify:**
```
app/Services/
  - WalletService.php
tests/Unit/
  - WalletServiceTest.php
tests/Feature/
  - WalletIntegrityTest.php
```

**Checklist:**
- [ ] Implement append-only transaction log
- [ ] Create balance calculation method
- [ ] Add credit/debit methods with validation
- [ ] Implement refund logic
- [ ] Create adjustment methods (admin only)
- [ ] Add transaction locking to prevent race conditions
- [ ] Ensure wallet math invariants
- [ ] Create comprehensive tests

#### PR 5.3: Invoice Generation System
**Files to Create/Modify:**
```
app/Services/
  - InvoiceService.php
app/Livewire/Invoice/
  - ViewInvoice.php
  - InvoiceList.php
resources/views/livewire/invoice/
  - view-invoice.blade.php
  - invoice-list.blade.php
```

**Checklist:**
- [ ] Create invoice generation service
- [ ] Generate unique invoice numbers
- [ ] Add line items with descriptions
- [ ] Calculate totals and tax (optional)
- [ ] Apply partner branding to invoices
- [ ] Mark invoices as immutable after issuance
- [ ] Create invoice PDF generation
- [ ] Create invoice list UI
- [ ] Create invoice detail view

#### PR 5.4: Admin Wallet Management
**Files to Create/Modify:**
```
app/Livewire/Admin/
  - ManageWallet.php
resources/views/livewire/admin/
  - manage-wallet.blade.php
```

**Checklist:**
- [ ] Create admin wallet management UI
- [ ] Add manual credit/debit functionality
- [ ] Implement adjustment reasons
- [ ] Show transaction history
- [ ] Add low balance alerts
- [ ] Create wallet top-up flow
- [ ] Add admin audit logging
- [ ] Implement wallet freeze/unfreeze

---

### Phase 6: Client Portal UI
**Status**: Not Started  
**Estimated PRs**: 5-6  
**Duration**: 1.5 weeks

#### PR 6.1: Layout & Navigation
**Files to Create/Modify:**
```
resources/views/layouts/
  - client.blade.php
  - guest.blade.php
resources/views/components/
  - sidebar.blade.php
  - header.blade.php
app/Livewire/
  - Navigation.php
```

**Checklist:**
- [ ] Create client portal layout
- [ ] Build sidebar navigation (Register Domain, Dashboard, My Domains, Invoices)
- [ ] Add header with user menu
- [ ] Implement white-label branding display
- [ ] Make layout responsive
- [ ] Add partner logo/colors dynamically
- [ ] Create guest layout for login/register
- [ ] Add breadcrumbs

#### PR 6.2: Client Dashboard
**Files to Create/Modify:**
```
app/Livewire/Client/
  - Dashboard.php
resources/views/livewire/client/
  - dashboard.blade.php
```

**Checklist:**
- [ ] Create dashboard metrics (Total Domains, Active, Pending, Expiring Soon)
- [ ] Show recent domains list
- [ ] Display recent invoices
- [ ] Add support contact block (partner-defined)
- [ ] Create quick action buttons
- [ ] Add domain expiry alerts
- [ ] Make dashboard widgets responsive
- [ ] Add loading states

#### PR 6.3: Domain List & Filter
**Files to Create/Modify:**
```
app/Livewire/Client/Domain/
  - DomainList.php
resources/views/livewire/client/domain/
  - domain-list.blade.php
```

**Checklist:**
- [ ] Create domain list component
- [ ] Add search and filter functionality
- [ ] Show domain status badges
- [ ] Display expiry dates
- [ ] Add auto-renew toggle
- [ ] Implement pagination
- [ ] Add bulk actions
- [ ] Create mobile-responsive table

#### PR 6.4: Domain Detail Page
**Files to Create/Modify:**
```
app/Livewire/Client/Domain/
  - DomainDetail.php
  - DomainHeader.php
resources/views/livewire/client/domain/
  - domain-detail.blade.php
  - domain-header.blade.php
```

**Checklist:**
- [ ] Create domain detail page
- [ ] Add domain header (name, status, verification, expiry)
- [ ] Add renew button
- [ ] Add sync button (fetch latest from registrar)
- [ ] Create left sub-navigation (Nameservers, DNS, Contacts, Documents)
- [ ] Implement tab switching
- [ ] Show domain info summary
- [ ] Add action buttons

#### PR 6.5: Domain Modules (Nameservers, DNS, Contacts)
**Files to Create/Modify:**
```
app/Livewire/Client/Domain/
  - NameserversModule.php
  - DnsModule.php
  - ContactsModule.php
resources/views/livewire/client/domain/
  - nameservers-module.blade.php
  - dns-module.blade.php
  - contacts-module.blade.php
```

**Checklist:**
- [ ] Create nameservers module UI
- [ ] Create DNS records management UI
- [ ] Create contacts management UI (Registrant, Admin, Tech, Billing)
- [ ] Add validation for each module
- [ ] Show registrar-specific requirements
- [ ] Add success/error notifications
- [ ] Implement real-time validation
- [ ] Create loading states

#### PR 6.6: Documents Module & Invoice Views
**Files to Create/Modify:**
```
app/Livewire/Client/Domain/
  - DocumentsModule.php
app/Livewire/Client/
  - InvoiceList.php
  - InvoiceDetail.php
resources/views/livewire/client/domain/
  - documents-module.blade.php
resources/views/livewire/client/
  - invoice-list.blade.php
  - invoice-detail.blade.php
```

**Checklist:**
- [ ] Create document upload UI (for ccTLDs)
- [ ] Show verification status
- [ ] Add document download
- [ ] Implement partner-controlled visibility
- [ ] Create invoice list page
- [ ] Create invoice detail page with PDF download
- [ ] Show payment status
- [ ] Add invoice filtering

---

### Phase 7: Partner Management System
**Status**: Not Started  
**Estimated PRs**: 4-5  
**Duration**: 1 week

#### PR 7.1: Partner Onboarding
**Files to Create/Modify:**
```
app/Livewire/Admin/Partner/
  - CreatePartner.php
  - PartnerList.php
resources/views/livewire/admin/partner/
  - create-partner.blade.php
  - partner-list.blade.php
app/Services/
  - PartnerOnboardingService.php
```

**Checklist:**
- [ ] Create partner registration form
- [ ] Set up initial wallet
- [ ] Create admin user for partner
- [ ] Send welcome email
- [ ] Create partner list for admin
- [ ] Add partner activation/suspension
- [ ] Create partner detail view
- [ ] Add partner search and filters

#### PR 7.2: White-Label Configuration
**Files to Create/Modify:**
```
app/Livewire/Partner/
  - BrandingSettings.php
  - DomainSettings.php
resources/views/livewire/partner/
  - branding-settings.blade.php
  - domain-settings.blade.php
```

**Checklist:**
- [ ] Create branding settings UI (logo, favicon, colors)
- [ ] Add logo upload functionality
- [ ] Implement color picker
- [ ] Create custom domain management UI
- [ ] Add DNS verification instructions
- [ ] Implement domain verification flow
- [ ] Add SSL certificate issuance (placeholder)
- [ ] Create preview functionality

#### PR 7.3: Partner Pricing Configuration
**Files to Create/Modify:**
```
app/Livewire/Partner/
  - PricingRules.php
resources/views/livewire/partner/
  - pricing-rules.blade.php
```

**Checklist:**
- [ ] Create pricing rules UI
- [ ] Add TLD selection
- [ ] Implement markup type selection (fixed/percentage)
- [ ] Add markup value input
- [ ] Show calculated final prices
- [ ] Add duration-based pricing (1y, 2y, 3y)
- [ ] Create pricing preview
- [ ] Add bulk pricing updates

#### PR 7.4: Partner Client Management
**Files to Create/Modify:**
```
app/Livewire/Partner/
  - ClientList.php
  - ClientDetail.php
resources/views/livewire/partner/
  - client-list.blade.php
  - client-detail.blade.php
```

**Checklist:**
- [ ] Create client list for partners
- [ ] Show client domains
- [ ] Display client invoices
- [ ] Add client suspension
- [ ] Implement client search
- [ ] Create client detail page
- [ ] Add client activity log
- [ ] Show client wallet transactions (if applicable)

#### PR 7.5: Partner Dashboard
**Files to Create/Modify:**
```
app/Livewire/Partner/
  - Dashboard.php
resources/views/livewire/partner/
  - dashboard.blade.php
```

**Checklist:**
- [ ] Create partner dashboard
- [ ] Show total clients
- [ ] Display total domains
- [ ] Show wallet balance
- [ ] Display recent transactions
- [ ] Add revenue metrics
- [ ] Show expiring domains
- [ ] Create quick actions

---

### Phase 8: Admin Panel
**Status**: Not Started  
**Estimated PRs**: 3-4  
**Duration**: 1 week

#### PR 8.1: Admin Dashboard & Navigation
**Files to Create/Modify:**
```
resources/views/layouts/
  - admin.blade.php
app/Livewire/Admin/
  - Dashboard.php
resources/views/livewire/admin/
  - dashboard.blade.php
```

**Checklist:**
- [ ] Create admin layout
- [ ] Build admin navigation
- [ ] Create admin dashboard with system metrics
- [ ] Show total partners, domains, revenue
- [ ] Display system health indicators
- [ ] Add recent activity feed
- [ ] Create quick links
- [ ] Add system alerts

#### PR 8.2: Registrar Management
**Files to Create/Modify:**
```
app/Livewire/Admin/
  - RegistrarList.php
  - RegistrarForm.php
resources/views/livewire/admin/
  - registrar-list.blade.php
  - registrar-form.blade.php
```

**Checklist:**
- [ ] Create registrar list UI
- [ ] Add registrar creation form
- [ ] Implement API credential management
- [ ] Add registrar activation/deactivation
- [ ] Create TLD assignment to registrars
- [ ] Add registrar testing functionality
- [ ] Show registrar health status
- [ ] Create sync triggers

#### PR 8.3: TLD & Pricing Management
**Files to Create/Modify:**
```
app/Livewire/Admin/
  - TldList.php
  - TldPricing.php
resources/views/livewire/admin/
  - tld-list.blade.php
  - tld-pricing.blade.php
```

**Checklist:**
- [ ] Create TLD management UI
- [ ] Add TLD activation/deactivation
- [ ] Implement pricing sync from registrar
- [ ] Show pricing history
- [ ] Add manual price overrides
- [ ] Create bulk pricing updates
- [ ] Show TLD features and requirements
- [ ] Add TLD search and filters

#### PR 8.4: System Administration
**Files to Create/Modify:**
```
app/Livewire/Admin/
  - SystemSettings.php
  - AuditLogs.php
  - Impersonate.php
resources/views/livewire/admin/
  - system-settings.blade.php
  - audit-logs.blade.php
```

**Checklist:**
- [ ] Create system settings UI
- [ ] Add audit log viewer
- [ ] Implement partner impersonation
- [ ] Add manual wallet adjustments UI
- [ ] Create domain management overrides
- [ ] Add system health checks
- [ ] Implement maintenance mode
- [ ] Create backup triggers

---

### Phase 9: Automation & Background Jobs
**Status**: Not Started  
**Estimated PRs**: 2-3  
**Duration**: 4-5 days

#### PR 9.1: Scheduled Jobs
**Files to Create/Modify:**
```
app/Console/Commands/
  - ScanExpiringDomains.php
  - ProcessAutoRenewals.php
  - SendRenewalReminders.php
  - SyncDomainStatus.php
  - SendLowBalanceAlerts.php
app/Console/Kernel.php
```

**Checklist:**
- [ ] Create expiry scan job (daily)
- [ ] Implement auto-renewal processor
- [ ] Create renewal reminder job
- [ ] Add domain status sync job
- [ ] Implement low balance alerts
- [ ] Schedule all jobs in Kernel
- [ ] Add job monitoring
- [ ] Create comprehensive logging for each job

#### PR 9.2: Queue Workers & Failed Jobs
**Files to Create/Modify:**
```
app/Jobs/
  - ProcessDomainRegistration.php
  - ProcessDomainRenewal.php
  - SendEmailJob.php
config/
  - queue.php
```

**Checklist:**
- [ ] Create domain registration queue job
- [ ] Implement domain renewal queue job
- [ ] Create email sending job
- [ ] Configure queue connection
- [ ] Add job retry logic
- [ ] Implement failed job handling
- [ ] Create job monitoring dashboard
- [ ] Add job performance metrics

#### PR 9.3: Notifications & Alerts
**Files to Create/Modify:**
```
app/Notifications/
  - DomainExpiryAlert.php
  - RenewalReminder.php
  - LowBalanceAlert.php
  - InvoiceGenerated.php
```

**Checklist:**
- [ ] Create domain expiry notifications
- [ ] Implement renewal reminders
- [ ] Add low balance alerts
- [ ] Create invoice notifications
- [ ] Configure notification channels (email, database)
- [ ] Add notification preferences
- [ ] Create notification logs
- [ ] Test notification delivery

---

### Phase 10: Email System
**Status**: Not Started  
**Estimated PRs**: 2  
**Duration**: 3-4 days

#### PR 10.1: Email Templates
**Files to Create/Modify:**
```
resources/views/emails/
  - domain-registered.blade.php
  - renewal-reminder.blade.php
  - invoice-issued.blade.php
  - payment-confirmation.blade.php
  - expiry-alert.blade.php
  - welcome.blade.php
```

**Checklist:**
- [ ] Create domain registration email template
- [ ] Design renewal reminder template
- [ ] Create invoice issued template
- [ ] Design payment confirmation template
- [ ] Create expiry alert template
- [ ] Design welcome email template
- [ ] Apply white-label branding to all templates
- [ ] Make templates responsive

#### PR 10.2: Email Service & Configuration
**Files to Create/Modify:**
```
app/Services/
  - EmailService.php
app/Mail/
  - DomainRegistered.php
  - RenewalReminder.php
  - InvoiceIssued.php
config/
  - mail.php
```

**Checklist:**
- [ ] Create email service wrapper
- [ ] Implement partner-specific sender configuration
- [ ] Add partner branding to emails
- [ ] Create mailable classes
- [ ] Configure mail drivers (SMTP, SendGrid, etc.)
- [ ] Add email logging
- [ ] Implement fallback sender
- [ ] Test email delivery

---

### Phase 11: Audit, Security & Compliance
**Status**: Not Started  
**Estimated PRs**: 2-3  
**Duration**: 4-5 days

#### PR 11.1: Audit Logging
**Files to Create/Modify:**
```
app/Observers/
  - DomainObserver.php
  - InvoiceObserver.php
  - WalletTransactionObserver.php
app/Services/
  - AuditService.php
app/Livewire/Admin/
  - AuditLogs.php
```

**Checklist:**
- [ ] Create model observers for audit logging
- [ ] Log all domain events
- [ ] Log all API requests/responses
- [ ] Log all billing actions
- [ ] Log all login activity
- [ ] Create audit log viewer
- [ ] Add audit log search and filters
- [ ] Implement audit log retention policy

#### PR 11.2: Data Integrity & Soft Deletes
**Files to Create/Modify:**
```
database/migrations/
  - 2024_xx_xx_add_soft_deletes_to_tables.php
app/Models/ (update models)
```

**Checklist:**
- [ ] Add soft deletes to all models (except financial)
- [ ] Ensure no hard deletes for financial data
- [ ] Add referential integrity constraints
- [ ] Create data integrity checks
- [ ] Add database backups configuration
- [ ] Implement cascading soft deletes
- [ ] Create data recovery methods
- [ ] Add data archiving

#### PR 11.3: Security Hardening
**Files to Create/Modify:**
```
app/Http/Middleware/
  - SecurityHeaders.php
  - RateLimiting.php
config/
  - cors.php
tests/Feature/Security/
  - TenantIsolationTest.php
  - XssProtectionTest.php
  - CsrfProtectionTest.php
```

**Checklist:**
- [ ] Add security headers middleware
- [ ] Implement rate limiting
- [ ] Configure CORS properly
- [ ] Add CSRF protection
- [ ] Implement XSS protection
- [ ] Add SQL injection prevention
- [ ] Create security tests
- [ ] Add penetration testing checklist
- [ ] Implement 2FA (future)

---

### Phase 12: Testing & Quality Assurance
**Status**: Not Started  
**Estimated PRs**: 2-3  
**Duration**: 1 week

#### PR 12.1: Unit Tests
**Files to Create/Modify:**
```
tests/Unit/
  - PricingEngineTest.php
  - WalletServiceTest.php
  - PartnerContextTest.php
  - DomainValidationTest.php
```

**Checklist:**
- [ ] Create pricing engine tests
- [ ] Add wallet service tests
- [ ] Create partner context tests
- [ ] Add domain validation tests
- [ ] Test all service classes
- [ ] Ensure 80%+ code coverage
- [ ] Add edge case tests
- [ ] Create failure scenario tests

#### PR 12.2: Feature Tests
**Files to Create/Modify:**
```
tests/Feature/
  - DomainRegistrationTest.php
  - DomainRenewalTest.php
  - InvoiceGenerationTest.php
  - WalletTransactionTest.php
  - TenantIsolationTest.php
```

**Checklist:**
- [ ] Create domain registration flow tests
- [ ] Add domain renewal flow tests
- [ ] Create invoice generation tests
- [ ] Add wallet transaction tests
- [ ] Test tenant isolation thoroughly
- [ ] Create authentication tests
- [ ] Add API integration tests
- [ ] Test error handling

#### PR 12.3: Integration Tests
**Files to Create/Modify:**
```
tests/Integration/
  - RegistrarIntegrationTest.php
  - EndToEndFlowTest.php
```

**Checklist:**
- [ ] Create registrar integration tests
- [ ] Add end-to-end flow tests
- [ ] Test complete registration flow
- [ ] Test renewal automation
- [ ] Test white-label functionality
- [ ] Create performance tests
- [ ] Add load testing
- [ ] Create browser tests (if needed)

---

### Phase 13: Documentation & Deployment
**Status**: Not Started  
**Estimated PRs**: 2  
**Duration**: 3-4 days

#### PR 13.1: API Documentation
**Files to Create/Modify:**
```
docs/
  - API.md
  - DEPLOYMENT.md
  - CONTRIBUTING.md
  - ARCHITECTURE.md
```

**Checklist:**
- [ ] Document all API endpoints
- [ ] Create deployment guide
- [ ] Write architecture documentation
- [ ] Add contributing guidelines
- [ ] Create developer setup guide
- [ ] Document registrar integration
- [ ] Add troubleshooting guide
- [ ] Create FAQ

#### PR 13.2: Production Readiness
**Files to Create/Modify:**
```
.env.example (update)
config/ (production settings)
docker/
  - Dockerfile
  - docker-compose.yml
```

**Checklist:**
- [ ] Update .env.example with all variables
- [ ] Configure production settings
- [ ] Create Docker configuration
- [ ] Add database migration guides
- [ ] Configure logging for production
- [ ] Set up error tracking (Sentry/Bugsnag)
- [ ] Add performance monitoring
- [ ] Create deployment checklist

---

## Technical Architecture

### Database Design Principles
1. **Append-Only Ledger** for wallet transactions
2. **Soft Deletes** for all models except financial data
3. **Referential Integrity** enforced at database level
4. **Scoped Queries** for tenant isolation
5. **Historical Tracking** for prices and changes

### Service Layer Architecture
```
Controllers/Livewire → Services → Repositories → Models
                    ↓
            Registrar Interface → Registrar Implementations
```

### Key Services
- **PartnerContextService**: Resolves partner from domain
- **PricingEngine**: Calculates prices with markup
- **WalletService**: Manages wallet transactions
- **DomainRegistrationService**: Handles domain registration
- **DomainRenewalService**: Manages renewals
- **InvoiceService**: Generates and manages invoices
- **EmailService**: Sends white-labeled emails
- **AuditService**: Logs all important events

### Multi-Tenancy Strategy
1. Every request resolves partner context
2. Global scopes applied to all tenant models
3. Hard foreign key constraints
4. No cross-partner queries possible
5. Partner context passed to Livewire components

---

## Deployment Strategy

### Environment Setup
1. **Development**: SQLite + local mail
2. **Staging**: PostgreSQL + Mailtrap
3. **Production**: PostgreSQL + SendGrid/SES + Redis

### Infrastructure Requirements
- PHP 8.2+
- PostgreSQL 14+
- Redis (for queues and cache)
- Supervisor (for queue workers)
- Cron (for scheduled jobs)
- SSL certificates (Let's Encrypt)

### Scaling Considerations
- Separate queue workers for heavy operations
- Database read replicas for reporting
- CDN for static assets
- Redis clustering for high availability
- Database partitioning for large datasets

---

## Success Criteria

### Functional Requirements ✅
- [ ] Multi-tenant with complete data isolation
- [ ] White-label branding fully functional
- [ ] Domain lifecycle managed end-to-end
- [ ] Wallet billing with transaction ledger
- [ ] Partner pricing rules applied correctly
- [ ] Automated renewals working
- [ ] Registrar-agnostic design
- [ ] Full audit trail

### Non-Functional Requirements ✅
- [ ] Handles 100k+ domains
- [ ] Deterministic billing (no rounding errors)
- [ ] UI matches design specifications
- [ ] API response time < 500ms (95th percentile)
- [ ] 99.9% uptime
- [ ] All financial data immutable
- [ ] Security audit passed

### Testing Requirements ✅
- [ ] 80%+ code coverage
- [ ] All critical paths tested
- [ ] Tenant isolation verified
- [ ] Registrar integration tested
- [ ] Performance benchmarks met
- [ ] Security tests passed

---

## Out of Scope (Future Phases)

These features are intentionally excluded from the initial implementation but can be added later:

1. **Hosting Management** - Web hosting, email hosting
2. **WHOIS Privacy Upsells** - Domain privacy protection
3. **Affiliate Payouts** - Commission system for affiliates
4. **Marketplace Integrations** - Third-party integrations
5. **Advanced Reporting** - Business intelligence dashboards
6. **Mobile Apps** - Native iOS/Android apps
7. **Multi-Language Support** - I18n/L10n
8. **Advanced 2FA** - Biometric, hardware keys

---

## Risk Mitigation

### Technical Risks
| Risk | Mitigation |
|------|------------|
| Registrar API changes | Abstract interface, version API calls |
| Data loss | Immutable financial records, regular backups |
| Billing errors | Comprehensive tests, manual verification |
| Security breach | Multi-layer security, audit logs, encryption |
| Performance issues | Caching, queue workers, database optimization |

### Business Risks
| Risk | Mitigation |
|------|------------|
| Partner churn | Excellent onboarding, support |
| Wallet fraud | Transaction limits, manual review for large amounts |
| Compliance issues | Full audit trail, data retention policies |
| Registrar downtime | Fallback mechanisms, status pages |

---

## Development Timeline

### Total Estimated Time: 12-14 weeks

**Month 1 (Weeks 1-4)**
- Phase 1: Database Schema & Core Models (1 week)
- Phase 2: Authentication & Multi-Tenancy (1 week)
- Phase 3: Registrar Integration Layer (1 week)
- Phase 4: Domain Operations (Start, 1 week)

**Month 2 (Weeks 5-8)**
- Phase 4: Domain Operations (Complete)
- Phase 5: Pricing Engine & Wallet System (1 week)
- Phase 6: Client Portal UI (1.5 weeks)
- Phase 7: Partner Management (Start)

**Month 3 (Weeks 9-12)**
- Phase 7: Partner Management (Complete, 1 week total)
- Phase 8: Admin Panel (1 week)
- Phase 9: Automation & Background Jobs (1 week)
- Phase 10: Email System (4 days)
- Phase 11: Audit, Security & Compliance (Start)

**Month 4 (Weeks 13-14)**
- Phase 11: Audit, Security & Compliance (Complete)
- Phase 12: Testing & Quality Assurance (1 week)
- Phase 13: Documentation & Deployment (4 days)

---

## Next Immediate Steps

1. **PR 1.1**: Start with core tables (Users & Roles)
2. **PR 1.2**: Create Partner & Branding tables
3. **PR 1.3**: Build Domain Management tables
4. **Continue sequentially** through the phases

---

## Notes

- Each PR should be **small and focused** (1-3 days of work)
- Always include **tests** with each PR
- **Code reviews** required before merging
- Follow **Laravel best practices** and conventions
- Keep **commits atomic** and well-documented
- Update this plan as needed based on learnings

---

**This is a production-ready, sellable platform when complete.**

**Not a toy project. Not a dashboard clone. A real registrar SaaS.**

---

*Last Updated: January 27, 2026*  
*Version: 1.0*  
*Status: Ready for Implementation*
