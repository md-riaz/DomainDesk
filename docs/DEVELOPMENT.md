# Development Guide

Complete development guide for contributing to DomainDesk.

## Table of Contents

1. [Development Environment](#development-environment)
2. [Running the Application](#running-the-application)
3. [Testing](#testing)
4. [Code Style](#code-style)
5. [Git Workflow](#git-workflow)
6. [Pull Request Process](#pull-request-process)
7. [Debugging](#debugging)
8. [Common Development Tasks](#common-development-tasks)

---

## Development Environment

### Prerequisites

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- SQLite 3.x
- Redis (optional for development)
- Git

### Initial Setup

```bash
# Clone repository
git clone https://github.com/md-riaz/DomainDesk.git
cd DomainDesk

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Build assets
npm run build
```

### IDE Setup

#### VSCode

Recommended extensions:
- PHP Intelephense
- Laravel Extension Pack
- Laravel Blade Snippets
- Tailwind CSS IntelliSense
- ESLint
- Prettier

`.vscode/settings.json`:
```json
{
  "php.suggest.basic": false,
  "blade.format.enable": true,
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  }
}
```

#### PhpStorm

1. Enable Laravel plugin
2. Set PHP interpreter to 8.2+
3. Configure Composer
4. Enable Laravel IDE Helper
5. Set code style to PSR-12

---

## Running the Application

### Development Server

#### Quick Start (Recommended)

```bash
# Start all services concurrently
composer dev
```

This command runs:
- Laravel dev server (http://localhost:8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server

#### Manual Start

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work --tries=3

# Terminal 3: Vite dev server
npm run dev

# Terminal 4: Log viewer (optional)
php artisan pail
```

### Using Laravel Sail (Docker)

```bash
# Start containers
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate

# Access application
http://localhost
```

### Environment Configuration

`.env` for development:
```ini
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
```

---

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/DomainRegistrationTest.php

# Run specific test method
php artisan test --filter test_domain_can_be_registered

# Run with coverage
php artisan test --coverage

# Run parallel tests
php artisan test --parallel
```

### Writing Tests

#### Feature Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DomainManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_view_their_domains()
    {
        $user = User::factory()->create(['role' => 'client']);
        $domain = Domain::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get('/domains');

        $response->assertStatus(200);
        $response->assertSee($domain->name);
    }

    public function test_client_cannot_view_other_domains()
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherDomain = Domain::factory()->create();

        $response = $this->actingAs($user)
            ->get('/domains');

        $response->assertStatus(200);
        $response->assertDontSee($otherDomain->name);
    }
}
```

#### Unit Test Example

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PricingService;
use App\Models\PartnerPricingRule;

class PricingServiceTest extends TestCase
{
    public function test_calculates_price_with_fixed_markup()
    {
        $basePrice = 10.00;
        $markup = 5.00;
        
        $rule = new PartnerPricingRule([
            'markup_type' => 'fixed',
            'markup_value' => $markup,
        ]);

        $service = new PricingService();
        $finalPrice = $service->calculatePrice($basePrice, $rule);

        $this->assertEquals(15.00, $finalPrice);
    }

    public function test_calculates_price_with_percentage_markup()
    {
        $basePrice = 10.00;
        $markupPercent = 30;
        
        $rule = new PartnerPricingRule([
            'markup_type' => 'percentage',
            'markup_value' => $markupPercent,
        ]);

        $service = new PricingService();
        $finalPrice = $service->calculatePrice($basePrice, $rule);

        $this->assertEquals(13.00, $finalPrice);
    }
}
```

#### Livewire Test Example

```php
<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Partner\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PartnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_view_dashboard()
    {
        $partner = User::factory()->create(['role' => 'partner']);

        Livewire::actingAs($partner)
            ->test(Dashboard::class)
            ->assertStatus(200)
            ->assertSee('Dashboard');
    }

    public function test_dashboard_shows_correct_statistics()
    {
        $partner = User::factory()->create(['role' => 'partner']);
        
        // Create test data
        // ...

        Livewire::actingAs($partner)
            ->test(Dashboard::class)
            ->assertSee('Total Clients')
            ->assertSee('Active Domains');
    }
}
```

### Test Database

Tests use SQLite in-memory database by default:

`phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Continuous Integration

Tests run automatically on every push via GitHub Actions (see `.github/workflows/tests.yml`).

---

## Code Style

### PHP Code Style (PSR-12)

Use Laravel Pint for automatic formatting:

```bash
# Format all files
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test

# Format specific file
./vendor/bin/pint app/Models/Domain.php
```

#### Pint Configuration

`pint.json`:
```json
{
    "preset": "laravel",
    "rules": {
        "blank_line_after_namespace": true,
        "line_ending": true,
        "no_unused_imports": true
    }
}
```

### Laravel Conventions

#### Naming Conventions

**Models**: Singular, PascalCase
```php
class Domain extends Model {}
class WalletTransaction extends Model {}
```

**Controllers**: Singular, PascalCase, with 'Controller' suffix
```php
class DomainController extends Controller {}
```

**Migrations**: Snake case with descriptive action
```php
2024_01_28_create_domains_table.php
2024_01_28_add_status_to_domains_table.php
```

**Routes**: Kebab case
```php
Route::get('/domain-search', ...);
Route::post('/wallet/add-funds', ...);
```

**Database Tables**: Plural, snake_case
```
domains
wallet_transactions
partner_pricing_rules
```

#### File Organization

```
app/
├── Http/
│   ├── Controllers/     # HTTP controllers
│   └── Middleware/      # Custom middleware
├── Livewire/           # Livewire components
│   ├── Client/
│   ├── Partner/
│   └── Admin/
├── Models/             # Eloquent models
│   └── Concerns/       # Model traits
├── Services/           # Business logic
│   └── Registrar/      # Registrar implementations
└── Helpers/            # Helper functions
```

#### Code Documentation

```php
/**
 * Register a new domain.
 *
 * @param  string  $domain  Domain name to register
 * @param  int  $period  Registration period in years
 * @return Domain
 * @throws InsufficientFundsException
 * @throws DomainUnavailableException
 */
public function register(string $domain, int $period = 1): Domain
{
    // Implementation
}
```

### JavaScript/Vue Style

Use Prettier for formatting:

```bash
# Format all JS files
npm run prettier

# ESLint
npm run lint
```

---

## Git Workflow

### Branch Strategy

- `main`: Production-ready code
- `develop`: Development branch
- `feature/*`: New features
- `bugfix/*`: Bug fixes
- `hotfix/*`: Emergency production fixes

### Creating a Feature Branch

```bash
# Update main branch
git checkout main
git pull origin main

# Create feature branch
git checkout -b feature/domain-search

# Make changes and commit
git add .
git commit -m "Add domain search functionality"

# Push to remote
git push origin feature/domain-search
```

### Commit Messages

Follow conventional commits format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

**Examples**:
```
feat(domains): add bulk domain search

Implement bulk domain availability check with caching
for improved performance. Supports up to 100 domains
per request.

Closes #123

fix(wallet): prevent negative balance on concurrent transactions

Add database transaction lock to prevent race condition
when multiple operations attempt to deduct funds simultaneously.

Fixes #456

docs(api): update authentication examples

Add Python and Node.js examples for API authentication.
```

### Branch Naming

```
feature/short-description
bugfix/issue-number-description
hotfix/critical-fix-description
```

Examples:
```
feature/domain-transfer
feature/pricing-calculator
bugfix/123-wallet-balance-display
hotfix/security-vulnerability
```

---

## Pull Request Process

### Before Creating PR

1. **Update your branch**:
   ```bash
   git checkout main
   git pull origin main
   git checkout feature/your-feature
   git rebase main
   ```

2. **Run tests**:
   ```bash
   php artisan test
   ```

3. **Format code**:
   ```bash
   ./vendor/bin/pint
   ```

4. **Update documentation** if needed

### Creating Pull Request

1. Push your branch to GitHub
2. Create PR from your branch to `main`
3. Fill in PR template:

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests added/updated
- [ ] All tests passing
- [ ] Manual testing completed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or documented)

## Related Issues
Closes #123
```

### Code Review

**For Authors**:
- Respond to feedback promptly
- Make requested changes
- Ask questions if unclear
- Keep PR focused and small

**For Reviewers**:
- Review within 24-48 hours
- Be constructive and specific
- Test the changes locally
- Approve when satisfied

### Merging

After approval:
1. Ensure CI passes
2. Rebase if needed
3. Squash commits (optional)
4. Merge to main

---

## Debugging

### Laravel Debugbar

Install for development:

```bash
composer require barryvdh/laravel-debugbar --dev
```

Provides:
- Query log
- Route information
- View data
- Timeline
- Memory usage

### Logging

```php
// Different log levels
Log::debug('Debug information');
Log::info('Informational message');
Log::warning('Warning message');
Log::error('Error occurred', ['context' => $data]);

// Log queries
DB::listen(function ($query) {
    Log::info($query->sql, $query->bindings);
});
```

View logs:
```bash
# Real-time logs
php artisan pail

# Or tail log file
tail -f storage/logs/laravel.log
```

### Tinker (REPL)

```bash
php artisan tinker
```

```php
>>> $user = User::find(1);
>>> $user->domains()->count();
>>> Domain::where('status', 'active')->get();
```

### Debugging Livewire

```php
// In Livewire component
public function mount()
{
    dd($this->property); // Dump and die
    ray($this->data); // Ray debug tool
}
```

### XDebug

Configure in `php.ini`:
```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9003
```

VSCode `launch.json`:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for XDebug",
            "type": "php",
            "request": "launch",
            "port": 9003
        }
    ]
}
```

---

## Common Development Tasks

### Creating a New Livewire Component

```bash
# Create component
php artisan make:livewire Partner/DomainList

# Creates:
# app/Livewire/Partner/DomainList.php
# resources/views/livewire/partner/domain-list.blade.php
```

### Creating a Migration

```bash
# Create table migration
php artisan make:migration create_domains_table

# Add column migration
php artisan make:migration add_status_to_domains_table

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (drop all)
php artisan migrate:fresh

# With seeding
php artisan migrate:fresh --seed
```

### Creating a Model

```bash
# Model only
php artisan make:model Domain

# With migration, factory, and seeder
php artisan make:model Domain -mfs

# With everything
php artisan make:model Domain --all
```

### Creating a Service

```bash
# Create service class
php artisan make:class Services/DomainSearchService
```

### Database Seeding

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=DomainSeeder
```

### Clearing Cache

```bash
# Clear all caches
php artisan optimize:clear

# Or individually
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Queue Management

```bash
# Process queue jobs
php artisan queue:work

# Process one job
php artisan queue:work --once

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Asset Compilation

```bash
# Development (watch mode)
npm run dev

# Production build
npm run build

# Preview production build
npm run preview
```

---

## Performance Optimization

### Development Performance

```bash
# Generate IDE helper files
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
php artisan ide-helper:models

# Enable OPcache in production
# php.ini:
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### Query Optimization

```php
// Eager loading (avoid N+1)
$domains = Domain::with(['user', 'nameservers'])->get();

// Chunk large datasets
Domain::chunk(100, function ($domains) {
    foreach ($domains as $domain) {
        // Process
    }
});

// Use cursors for memory efficiency
foreach (Domain::cursor() as $domain) {
    // Process
}
```

### Database Indexes

```php
// Add index in migration
$table->index('domain_name');
$table->index(['partner_id', 'status']);
$table->unique('email');
```

---

## Helpful Resources

### Official Documentation
- [Laravel 12](https://laravel.com/docs/12.x)
- [Livewire 4](https://livewire.laravel.com/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)

### Community
- [Laravel News](https://laravel-news.com)
- [Laracasts](https://laracasts.com)
- [Laravel.io](https://laravel.io)

### Tools
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)
- [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper)
- [Ray](https://myray.app)
- [Telescope](https://laravel.com/docs/telescope)

---

**Last Updated**: January 2025

For questions, open an issue on GitHub or contact the development team.
