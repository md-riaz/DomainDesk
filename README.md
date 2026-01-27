# DomainDesk

> **White-Label Domain Reseller & Client Billing Platform**

A comprehensive SaaS platform built with Laravel 12 and Livewire 4, enabling partners to sell domains under their own brand while providing clients with an intuitive domain management experience.

[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-4-purple.svg)](https://livewire.laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📚 Documentation

- **[IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md)** - Comprehensive 13-phase implementation roadmap (36KB, 1387 lines)
- **[QUICK_START_GUIDE.md](QUICK_START_GUIDE.md)** - Quick reference for getting started
- **[SETUP_SUMMARY.md](SETUP_SUMMARY.md)** - Laravel & Livewire setup details
- **[LARAVEL_LLM_DOCS.md](LARAVEL_LLM_DOCS.md)** - Laravel framework reference
- **[LIVEWIRE_DOCS.md](LIVEWIRE_DOCS.md)** - Livewire component reference

---

## 🚀 Features

### Core Capabilities
- ✅ **Multi-Tenant Architecture** - Complete data isolation by partner
- ✅ **White-Label Branding** - Custom domains, logos, colors, emails, invoices
- ✅ **Domain Lifecycle Management** - Register, renew, transfer, and manage domains
- ✅ **Wallet-Based Billing** - Append-only ledger for financial integrity
- ✅ **Partner Pricing Rules** - Custom markup (fixed/percentage) per TLD
- ✅ **Automated Renewals** - Scheduled auto-renewals with wallet checks
- ✅ **Full Audit Trail** - Complete compliance logging
- ✅ **Registrar-Agnostic** - Abstraction layer for multiple registrars

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

```bash
# Clone repository
git clone https://github.com/md-riaz/DomainDesk.git
cd DomainDesk

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# The database is already set up! Just start the server
php artisan serve

# Or run the full development environment
composer dev
```

---

## 🎯 Project Status

| Phase | Status | Duration | PRs |
|-------|--------|----------|-----|
| Phase 0: Foundation | ✅ Complete | 1 day | 1 |
| Phase 1: Database Schema | 📋 Next | 1 week | 6 |
| Phase 2: Auth & Multi-Tenancy | 📋 Planned | 4-5 days | 3 |
| Phase 3: Registrar Integration | 📋 Planned | 1 week | 4 |
| Phase 4: Domain Operations | 📋 Planned | 1.5 weeks | 5 |
| Phases 5-13 | 📋 Planned | 8-9 weeks | 35+ |

**Total Timeline**: 12-14 weeks for full implementation

See [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) for complete breakdown.

---

## 🔑 Key Architecture Decisions

### Multi-Tenancy
- Every request resolves partner context from domain
- Global scopes enforce data isolation
- Hard foreign key constraints on all tables
- Cross-partner access impossible by design

### Financial Integrity
- Append-only wallet transaction ledger
- Immutable invoices after issuance
- Deterministic pricing (no floating point errors)
- Full audit trail for all financial operations

### White-Label First
- Partner branding loaded on every request
- Custom domain resolution with DNS verification
- Branded emails and invoices
- Partner-specific support contacts

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

Target: 80%+ code coverage

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

We follow Laravel conventions and best practices:

1. Fork the repository
2. Create a feature branch
3. Write tests for your changes
4. Follow PSR-12 coding standards
5. Submit a pull request

See [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) for detailed architecture and patterns.

---

## 🔐 Security

- Multi-tenant data isolation enforced at database level
- CSRF protection on all forms
- XSS prevention with Blade escaping
- SQL injection prevention via Eloquent
- Rate limiting on authentication endpoints
- Full audit logging for compliance

Report security vulnerabilities to: [security@domaindesk.example.com](mailto:security@domaindesk.example.com)

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
