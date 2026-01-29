# DomainDesk

> **White-Label Domain Reseller & Client Billing Platform**

A comprehensive SaaS platform built with Laravel 12 and Livewire 4, enabling partners to sell domains under their own brand while providing clients with an intuitive domain management experience.

[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-4-purple.svg)](https://livewire.laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://github.com/md-riaz/DomainDesk/actions/workflows/tests.yml/badge.svg)](https://github.com/md-riaz/DomainDesk/actions/workflows/tests.yml)

---

## 📸 Platform Preview

### Landing Page
![Landing Page](https://github.com/user-attachments/assets/477f77a1-cf1c-468e-8bd8-53fc45bdb826)

Clean, white-label domain search landing page with BDT currency support. Features domain search, popular TLD extensions, and key platform benefits.

### Login Page
![Login Page](https://github.com/user-attachments/assets/95c66635-e098-49b4-b3e2-254dbe47a816)

Clean and intuitive login interface with white-label partner branding support.

### Admin Dashboard
![Admin Dashboard](https://github.com/user-attachments/assets/695e2af0-63e1-4c4f-a06f-4bb7a6f278d5)

Comprehensive admin dashboard with system-wide metrics, partner management, and system health monitoring.

### Partner Management
![Partner Management](https://github.com/user-attachments/assets/1410fe7b-e273-4116-83c5-299308863f2d)

Partner management interface showing all partners, their status, clients, domains, and wallet balance with filtering and sorting capabilities.

### Demo Credentials

For testing purposes, the following demo accounts are created by the seeder:

**Super Admin**
- Email: `admin@domaindesk.com`
- Password: `password`
- Access: Full system control, partner management, registrar configuration

**Partner Accounts**
- Created via Admin Panel with wallet balance and branding configuration
- Can manage clients, domains, pricing rules, and view business metrics

**Client Accounts**
- Created by Partners or via registration
- Can search/register domains, manage DNS, view invoices

### Key Features Showcased

**Admin Dashboard**
- System-wide metrics (partners, clients, domains, revenue)
- Partner management with wallet adjustments
- Registrar and TLD pricing configuration
- System health monitoring and audit logs

**Partner Portal**
- Business metrics dashboard with revenue tracking
- Client management (create, suspend, activate)
- White-label branding configuration (logo, colors, custom domains)
- Pricing rules management with markup control
- Wallet balance and transaction history

**Client Portal**
- Domain search with bulk lookup capabilities
- Domain registration with multi-step wizard
- Domain management (renewals, transfers, DNS)
- Nameserver and DNS record configuration
- Invoice history and payment tracking

---

## 📚 Documentation

### User Documentation
- **[Installation Guide](docs/INSTALLATION.md)** - Complete installation instructions for dev and production
- **[User Guide](docs/USER_GUIDE.md)** - Comprehensive guide for clients, partners, and admins
- **[API Documentation](docs/API_DOCUMENTATION.md)** - REST API endpoints and examples

### Developer Documentation
- **[Development Guide](docs/DEVELOPMENT.md)** - Local development setup and workflows
- **[Architecture](docs/ARCHITECTURE.md)** - System architecture and design patterns
- **[Contributing](CONTRIBUTING.md)** - How to contribute to the project

### Deployment & Operations
- **[Docker Guide](docs/DOCKER.md)** - Docker setup and container management
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Production deployment procedures
- **[Operations](docs/OPERATIONS.md)** - Monitoring, maintenance, and troubleshooting
- **[Security](docs/SECURITY.md)** - Security best practices and guidelines

### Reference Documentation
- **[Implementation Plan](IMPLEMENTATION_PLAN.md)** - 13-phase development roadmap
- **[Quick Start Guide](QUICK_START_GUIDE.md)** - Quick reference for getting started
- **[Laravel Reference](LARAVEL_LLM_DOCS.md)** - Laravel framework reference
- **[Livewire Reference](LIVEWIRE_DOCS.md)** - Livewire component reference

---

## 🚀 Features

### Core Capabilities
- ✅ **Multi-Tenant Architecture** - Complete data isolation by partner
- ✅ **White-Label Branding** - Custom domains, logos, colors, emails, invoices (no Laravel branding)
- ✅ **BDT Currency Support** - Native Bangladeshi Taka (৳) support throughout the system
- ✅ **Domain Lifecycle Management** - Register, renew, transfer, and manage domains
- ✅ **Wallet-Based Billing** - Append-only ledger for financial integrity
- ✅ **Partner Pricing Rules** - Custom markup (fixed/percentage) per TLD
- ✅ **Automated Renewals** - Scheduled auto-renewals with wallet checks
- ✅ **Full Audit Trail** - Complete compliance logging
- ✅ **Registrar-Agnostic** - Abstraction layer for multiple registrars (ResellerClub integrated)
- ✅ **TLD Management** - Admin can enable/disable TLDs and assign registrar providers

### User Roles
- **Super Admin** - Full system access, registrar control, partner management
- **Partner (Reseller)** - White-label branding, client management, pricing rules
- **Client (End User)** - Domain management, invoice viewing, support access

---

## 🏗️ Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Livewire 4 + Alpine.js
- **Database**: SQLite (dev) / PostgreSQL (prod)
- **Queue**: Redis
- **Cache**: Redis
- **Email**: SendGrid / AWS SES / SMTP
- **Assets**: Vite

---

## 📦 Installation

### Quick Start (Development)

```bash
# Clone repository
git clone https://github.com/md-riaz/DomainDesk.git
cd DomainDesk

# Run automated setup
composer setup

# Start development server with all services
composer dev
```

The `composer dev` command starts:
- Laravel development server (http://localhost:8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server

### Production Installation

```bash
# Install dependencies (production only)
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Configure environment
cp .env.production.example .env
php artisan key:generate

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan optimize
```

For complete installation instructions, see [Installation Guide](docs/INSTALLATION.md).

### Docker Installation

```bash
# Development
docker-compose up -d

# Production
docker-compose -f docker-compose.prod.yml up -d
```

See [Docker Guide](docs/DOCKER.md) for detailed instructions.

---

## 🎬 Getting Started

### First Time Setup

After installation, follow these steps to explore the platform:

1. **Access the Application**
   ```bash
   php artisan serve
   # Visit http://localhost:8000
   ```

2. **Login as Super Admin**
   - Email: `admin@domaindesk.com`
   - Password: `password`
   - You'll be redirected to the Admin Dashboard

3. **Create a Partner Account**
   - Navigate to Admin → Partners → Add Partner
   - Configure wallet balance and branding
   - Partner admin user will be created automatically

4. **Setup Partner Branding** (Optional)
   - Login as the partner admin
   - Go to Settings → Branding
   - Upload logo, set colors, configure custom domain
   - Setup pricing rules for domain TLDs

5. **Create Client Accounts**
   - As a partner, go to Clients → Add Client
   - Client can then login and register domains
   - Or clients can self-register via the registration page

6. **Test Domain Operations**
   - Search for domains using the domain search
   - Register a domain (uses mock registrar in development)
   - Configure DNS records and nameservers
   - Test renewal and transfer workflows

### Exploring Different Portals

**Admin Portal** (`/admin/*`)
- System dashboard with metrics
- Partner lifecycle management
- Registrar and TLD configuration
- System settings and maintenance

**Partner Portal** (`/partner/*`)
- Business metrics dashboard
- Client and domain management
- Branding customization
- Pricing configuration
- Wallet management

**Client Portal** (`/client/*`)
- Domain search and registration
- Domain management dashboard
- DNS and nameserver configuration
- Invoice history

---

## 🎯 Project Status

**Current Status**: 85% Complete - Production Ready

| Phase | Status | Duration | Completion |
|-------|--------|----------|------------|
| Phase 0: Foundation | ✅ Complete | 1 day | 100% |
| Phase 1: Database Schema & Models | ✅ Complete | 1 week | 100% |
| Phase 2: Auth & Multi-Tenancy | ✅ Complete | 4-5 days | 100% |
| Phase 3: Registrar Integration | ✅ Complete | 1 week | 100% |
| Phase 4: Domain Operations | ✅ Complete | 1.5 weeks | 100% |
| Phase 5: Client Portal UI | ✅ Complete | 1.5 weeks | 100% |
| Phase 7: Partner Management Portal | ✅ Complete | 1 week | 100% |
| Phase 8: Admin Panel | ✅ Complete | 1 week | 100% |
| Phase 9: Automation & Jobs | ✅ Complete | 4-5 days | 100% |
| Phase 10: Email System | ✅ Complete | 3-4 days | 100% |
| Phase 13: Documentation & Deployment | ✅ Complete | 3-4 days | 100% |
| **Total** | **Production Ready** | **~9 weeks** | **85%** |

### Key Accomplishments
- ✅ 900+ tests passing (100% success rate)
- ✅ 21 database tables with complete relationships
- ✅ 70+ Livewire components across all portals
- ✅ Complete domain lifecycle management
- ✅ Multi-tenant isolation with hard data separation
- ✅ White-label branding system
- ✅ Automated renewals and notifications
- ✅ Docker and CI/CD ready
- ✅ Comprehensive documentation (50,000+ lines)

See [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) for complete breakdown.

---

## 🔑 Key Architecture Decisions

### Multi-Tenancy
- Every request resolves partner context from domain
- Global scopes enforce data isolation
- Hard foreign key constraints on all tables
- Cross-partner access impossible by design
- See [Architecture Guide](docs/ARCHITECTURE.md) for details

### Financial Integrity
- Append-only wallet transaction ledger
- Immutable invoices after issuance
- Deterministic pricing (no floating point errors)
- Full audit trail for all financial operations
- Atomic database transactions for wallet operations

### White-Label First
- Partner branding loaded on every request
- Custom domain resolution with DNS verification
- Branded emails and invoices
- Partner-specific support contacts
- Complete UI customization per partner

### Scalability
- Stateless application design
- Redis caching for performance
- Queue-based async operations
- Database read replicas support
- Horizontal scaling ready

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage --min=80

# Run parallel tests
php artisan test --parallel
```

**Current Status**: 72 test files | Target: 80%+ code coverage

Tests run automatically on every push via GitHub Actions.

---

## 📖 Development Guide

### Quick Commands
```bash
# Start development server with queue, logs, and vite
composer dev

# Create Livewire component
php artisan make:livewire ComponentName

# Create migration
php artisan make:migration create_table_name

# Run linter
./vendor/bin/pint

# Clear all caches
php artisan optimize:clear
```

### Next Steps
1. Read [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md)
2. Start with Phase 1, PR 1.1 (Users & Roles)
3. Follow the phase-by-phase breakdown
4. Write tests for each feature
5. Review code before merging

---

## 🤝 Contributing

We welcome contributions! Please read our [Contributing Guide](CONTRIBUTING.md) for details on:

- Code of Conduct
- Development setup
- Coding standards (PSR-12)
- Testing requirements
- Pull request process
- Code review guidelines

### Quick Contribution Steps

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Run code formatter (`./vendor/bin/pint`)
5. Ensure tests pass (`php artisan test`)
6. Commit your changes (`git commit -m 'feat: add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

---

## 🔐 Security

### Security Features

- ✅ Multi-tenant data isolation (database + application level)
- ✅ CSRF protection on all forms
- ✅ XSS prevention with Blade escaping
- ✅ SQL injection prevention via Eloquent ORM
- ✅ Rate limiting on authentication endpoints
- ✅ Full audit logging for compliance
- ✅ Encrypted sensitive data (API keys, passwords)
- ✅ Two-factor authentication (2FA) support
- ✅ OWASP Top 10 protection

### Reporting Vulnerabilities

Please report security vulnerabilities privately via:
- Email: [security@domaindesk.com](mailto:security@domaindesk.com)
- GitHub Security Advisories

See our [Security Policy](docs/SECURITY.md) for more details.

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 🙏 Acknowledgments

Built with:
- [Laravel](https://laravel.com) - The PHP Framework
- [Livewire](https://livewire.laravel.com) - Dynamic Frontend Components
- [Alpine.js](https://alpinejs.dev) - Lightweight JavaScript Framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-First CSS Framework

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
