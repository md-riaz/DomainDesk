# Contributing to DomainDesk

Thank you for considering contributing to DomainDesk! This document outlines the process and guidelines for contributing to the project.

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [How to Contribute](#how-to-contribute)
4. [Development Process](#development-process)
5. [Coding Standards](#coding-standards)
6. [Testing Requirements](#testing-requirements)
7. [Documentation Requirements](#documentation-requirements)
8. [Pull Request Process](#pull-request-process)
9. [Code Review Process](#code-review-process)
10. [Community](#community)

---

## Code of Conduct

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

**Positive behavior includes:**
- Using welcoming and inclusive language
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Unacceptable behavior includes:**
- Harassment, trolling, or derogatory comments
- Publishing others' private information
- Any conduct which could reasonably be considered inappropriate

### Enforcement

Instances of abusive, harassing, or otherwise unacceptable behavior may be reported by contacting the project team. All complaints will be reviewed and investigated promptly and fairly.

---

## Getting Started

### Prerequisites

Before contributing, ensure you have:
- PHP 8.2 or higher
- Composer 2.x
- Node.js 18.x or higher
- Git
- Familiarity with Laravel and Livewire

### Setting Up Your Development Environment

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/DomainDesk.git
   cd DomainDesk
   ```
3. **Add upstream remote**:
   ```bash
   git remote add upstream https://github.com/md-riaz/DomainDesk.git
   ```
4. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```
5. **Set up environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   ```
6. **Run the application**:
   ```bash
   composer dev
   ```

---

## How to Contribute

### Types of Contributions

We welcome the following types of contributions:

#### 🐛 **Bug Reports**
- Use GitHub Issues
- Search existing issues first
- Provide detailed reproduction steps
- Include environment details (PHP version, OS, etc.)

#### ✨ **Feature Requests**
- Use GitHub Discussions for proposals
- Explain the use case and benefits
- Be open to feedback and alternative solutions

#### 📝 **Documentation**
- Fix typos and clarify unclear sections
- Add examples and use cases
- Improve installation instructions
- Translate documentation

#### 💻 **Code Contributions**
- Bug fixes
- New features (discuss first in GitHub Discussions)
- Performance improvements
- Test coverage improvements
- Refactoring

---

## Development Process

### 1. Choose an Issue

- Browse [open issues](https://github.com/md-riaz/DomainDesk/issues)
- Look for issues labeled `good first issue` or `help wanted`
- Comment on the issue to claim it
- Wait for maintainer approval before starting work

### 2. Create a Branch

```bash
# Update your local repository
git checkout main
git pull upstream main

# Create a feature branch
git checkout -b feature/your-feature-name
```

**Branch naming conventions**:
- `feature/short-description` - New features
- `bugfix/issue-number-description` - Bug fixes
- `docs/what-changed` - Documentation updates
- `refactor/what-refactored` - Code refactoring

### 3. Make Changes

- Write clean, readable code
- Follow existing code style
- Add comments for complex logic
- Keep commits focused and atomic

### 4. Write Tests

- Add tests for new functionality
- Update tests for changed functionality
- Ensure all tests pass

### 5. Update Documentation

- Update relevant documentation files
- Add/update code comments
- Update CHANGELOG.md (if applicable)

### 6. Commit Changes

```bash
git add .
git commit -m "feat(domain): add bulk domain search"
```

Follow [conventional commit format](#commit-message-format).

### 7. Push and Create Pull Request

```bash
git push origin feature/your-feature-name
```

Then create a pull request on GitHub.

---

## Coding Standards

### PHP Code Style (PSR-12)

We follow PSR-12 coding standards with Laravel conventions.

#### Formatting

Use Laravel Pint for automatic formatting:

```bash
# Format all files
./vendor/bin/pint

# Check without formatting
./vendor/bin/pint --test
```

#### Naming Conventions

**Classes**: PascalCase
```php
class DomainRegistrationService {}
class PartnerPricingRule {}
```

**Methods**: camelCase
```php
public function registerDomain() {}
public function calculatePrice() {}
```

**Variables**: camelCase
```php
$domainName = 'example.com';
$userBalance = 100.00;
```

**Constants**: UPPER_SNAKE_CASE
```php
const MAX_RETRY_ATTEMPTS = 3;
const DEFAULT_TTL = 3600;
```

**Database Tables**: snake_case, plural
```php
domains
wallet_transactions
partner_pricing_rules
```

#### Type Hints

Always use type hints:

```php
// Good
public function register(string $domain, int $period): Domain
{
    // ...
}

// Bad
public function register($domain, $period)
{
    // ...
}
```

#### Doc Blocks

Add doc blocks for all public methods:

```php
/**
 * Register a new domain.
 *
 * @param  string  $domain  Domain name to register
 * @param  int  $period  Registration period in years
 * @return Domain  Registered domain instance
 * @throws InsufficientFundsException  When wallet balance is too low
 * @throws DomainUnavailableException  When domain is not available
 */
public function register(string $domain, int $period = 1): Domain
{
    // Implementation
}
```

### JavaScript/TypeScript

#### Formatting

Use Prettier for formatting:

```bash
npm run prettier
```

#### Style Guide

- Use ES6+ features
- Prefer `const` over `let`
- Use arrow functions where appropriate
- Avoid `var`

```javascript
// Good
const calculatePrice = (basePrice, markup) => {
    return basePrice + markup;
};

// Bad
var calculatePrice = function(basePrice, markup) {
    return basePrice + markup;
};
```

### Blade Templates

- Use `@` directives over PHP tags
- Keep logic minimal in views
- Extract complex logic to view composers or components

```blade
{{-- Good --}}
@if($domain->isExpiring())
    <span class="text-red-600">Expiring Soon</span>
@endif

{{-- Bad --}}
<?php if ($domain->expires_at < now()->addDays(30)): ?>
    <span>Expiring Soon</span>
<?php endif; ?>
```

---

## Testing Requirements

### Test Coverage

- All new features must include tests
- Aim for 80%+ code coverage
- Test happy paths and edge cases

### Types of Tests

#### Feature Tests

Test complete features and user workflows:

```php
public function test_client_can_register_domain()
{
    $user = User::factory()->create(['role' => 'client']);
    $user->wallet->credit(100); // Add funds

    $response = $this->actingAs($user)
        ->post('/domains/register', [
            'domain' => 'test.com',
            'period' => 1,
        ]);

    $response->assertRedirect('/domains');
    $this->assertDatabaseHas('domains', [
        'name' => 'test.com',
        'user_id' => $user->id,
    ]);
}
```

#### Unit Tests

Test individual methods and classes:

```php
public function test_pricing_service_calculates_correct_markup()
{
    $service = new PricingService();
    $basePrice = 10.00;
    $markup = 30; // 30%

    $result = $service->applyPercentageMarkup($basePrice, $markup);

    $this->assertEquals(13.00, $result);
}
```

#### Livewire Tests

Test Livewire components:

```php
public function test_domain_list_component_filters_by_status()
{
    $user = User::factory()->create();
    Domain::factory()->count(5)->create(['status' => 'active']);
    Domain::factory()->count(3)->create(['status' => 'expired']);

    Livewire::actingAs($user)
        ->test(DomainList::class)
        ->set('filter', 'active')
        ->assertCount('domains', 5);
}
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter test_client_can_register_domain

# Run with coverage
php artisan test --coverage

# Run specific suite
php artisan test --testsuite=Feature
```

### Test Database

Tests use SQLite in-memory database. Migrations run automatically for each test.

---

## Documentation Requirements

### Code Documentation

- Add PHPDoc blocks for all public methods
- Document complex algorithms
- Explain "why" not just "what"
- Keep comments up-to-date

### User Documentation

When adding features that affect users:
- Update relevant sections in `docs/USER_GUIDE.md`
- Add examples and screenshots if helpful
- Update FAQ if applicable

### Developer Documentation

When changing architecture or patterns:
- Update `docs/ARCHITECTURE.md`
- Update `docs/DEVELOPMENT.md`
- Add examples to help other developers

### API Documentation

When adding/changing API endpoints:
- Update `docs/API_DOCUMENTATION.md`
- Include request/response examples
- Document error codes

---

## Pull Request Process

### Before Creating PR

1. **Ensure tests pass**:
   ```bash
   php artisan test
   ```

2. **Format code**:
   ```bash
   ./vendor/bin/pint
   ```

3. **Update documentation**

4. **Commit all changes**

5. **Rebase on main**:
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

### Creating the PR

1. Push your branch to your fork
2. Go to the DomainDesk repository
3. Click "New Pull Request"
4. Select your branch
5. Fill in the PR template

### PR Template

```markdown
## Description
Brief description of what this PR does

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Related Issue
Fixes #(issue number)

## Testing
- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] Livewire tests added/updated (if applicable)
- [ ] All tests passing locally
- [ ] Manual testing completed

## Documentation
- [ ] Code comments added/updated
- [ ] User documentation updated
- [ ] API documentation updated (if applicable)
- [ ] Architecture documentation updated (if applicable)

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] No console errors or warnings
- [ ] Existing tests still pass
- [ ] Documentation is clear and complete

## Screenshots (if applicable)
Add screenshots for UI changes

## Additional Notes
Any additional information reviewers should know
```

### PR Size Guidelines

- Keep PRs focused and small (< 500 lines preferred)
- One feature/fix per PR
- Split large changes into multiple PRs
- Link related PRs in descriptions

---

## Code Review Process

### For Authors

**Responding to Reviews:**
- Respond to all comments
- Make requested changes
- Ask questions if unclear
- Be open to feedback
- Keep discussion professional

**After Approval:**
- Ensure CI passes
- Rebase if needed
- Squash commits if requested
- Wait for merge from maintainer

### For Reviewers

**What to Check:**
- Code quality and style
- Test coverage
- Documentation
- Performance implications
- Security concerns
- Breaking changes

**Providing Feedback:**
- Be constructive and specific
- Explain the "why" behind suggestions
- Distinguish between required and optional changes
- Acknowledge good work
- Test changes locally when possible

**Review Timeline:**
- Aim to review within 24-48 hours
- For urgent fixes, review within a few hours
- If unable to review, mention in comments

---

## Commit Message Format

We follow the [Conventional Commits](https://www.conventionalcommits.org/) specification.

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Code style changes (formatting, missing semicolons, etc.)
- `refactor`: Code refactoring (no functional changes)
- `perf`: Performance improvements
- `test`: Adding or updating tests
- `chore`: Maintenance tasks, dependency updates
- `ci`: CI/CD changes

### Examples

```
feat(domain): add bulk domain search functionality

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

---

## Community

### Getting Help

- **Documentation**: Start with [docs/](docs/)
- **GitHub Discussions**: Ask questions and discuss features
- **GitHub Issues**: Report bugs
- **Email**: support@domaindesk.com

### Stay Updated

- Watch the repository for updates
- Follow release notes
- Join discussions on new features

### Recognition

Contributors are recognized in:
- CHANGELOG.md
- Release notes
- Contributors page

---

## License

By contributing to DomainDesk, you agree that your contributions will be licensed under the MIT License.

---

## Questions?

If you have any questions about contributing, please:
1. Check existing documentation
2. Search GitHub Discussions
3. Open a new discussion
4. Contact maintainers

Thank you for contributing to DomainDesk! 🚀

---

**Last Updated**: January 2025
