# Security Guide for DomainDesk

This comprehensive guide covers security best practices, implementation details, and procedures for DomainDesk.

## Table of Contents

- [Security Best Practices](#security-best-practices)
- [Authentication & Authorization](#authentication--authorization)
- [Data Encryption](#data-encryption)
- [Audit Logging](#audit-logging)
- [OWASP Top 10 Protection](#owasp-top-10-protection)
- [Vulnerability Management](#vulnerability-management)
- [Security Headers](#security-headers)
- [Incident Response Plan](#incident-response-plan)
- [Penetration Testing Guidelines](#penetration-testing-guidelines)

## Security Best Practices

### General Principles

1. **Defense in Depth**: Multiple layers of security controls
2. **Least Privilege**: Minimal access rights for users and services
3. **Security by Default**: Secure configurations out of the box
4. **Regular Updates**: Keep all dependencies and systems current
5. **Security Awareness**: Train team members on security practices

### Environment Security

```bash
# .env file permissions
chmod 600 .env
chown domaindesk:domaindesk .env

# Never commit .env to version control
echo ".env" >> .gitignore

# Use different keys for each environment
php artisan key:generate --show
```

### Secure Configuration Checklist

```bash
# Production .env settings
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-secure-key-here

# Force HTTPS
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# Disable directory listing
# In nginx.conf:
# autoindex off;

# Hide server information
# In nginx.conf:
# server_tokens off;

# Set secure headers (see Security Headers section)
```

## Authentication & Authorization

### User Authentication

#### Password Requirements

Configure in `config/auth.php`:

```php
'password' => [
    'min_length' => 12,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special_chars' => true,
    'max_attempts' => 5,
    'lockout_duration' => 900, // 15 minutes
],
```

Implement in `app/Rules/SecurePassword.php`:

```php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SecurePassword implements Rule
{
    public function passes($attribute, $value)
    {
        return strlen($value) >= 12
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[a-z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[^A-Za-z0-9]/', $value);
    }

    public function message()
    {
        return 'Password must be at least 12 characters with uppercase, lowercase, numbers, and special characters.';
    }
}
```

#### Multi-Factor Authentication (2FA)

Install 2FA package:

```bash
docker compose exec app composer require pragmarx/google2fa-laravel
```

Implement 2FA:

```php
// app/Http/Controllers/Auth/TwoFactorController.php
namespace App\Http\Controllers\Auth;

use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function enable()
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        auth()->user()->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => false, // Enable after verification
        ]);
        
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            auth()->user()->email,
            $secret
        );
        
        return view('auth.2fa.enable', compact('qrCodeUrl', 'secret'));
    }
    
    public function verify(Request $request)
    {
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(
            decrypt(auth()->user()->two_factor_secret),
            $request->code
        );
        
        if ($valid) {
            auth()->user()->update(['two_factor_enabled' => true]);
            return redirect()->route('dashboard')->with('success', '2FA enabled');
        }
        
        return back()->withErrors(['code' => 'Invalid verification code']);
    }
}
```

#### Session Management

Configure secure sessions in `config/session.php`:

```php
'lifetime' => 120, // 2 hours
'expire_on_close' => true,
'encrypt' => true,
'http_only' => true,
'same_site' => 'strict',
'secure' => true, // HTTPS only
```

Implement session timeout:

```php
// app/Http/Middleware/SessionTimeout.php
namespace App\Http\Middleware;

class SessionTimeout
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            $lastActivity = session('last_activity', time());
            $timeout = config('session.lifetime') * 60;
            
            if (time() - $lastActivity > $timeout) {
                auth()->logout();
                session()->flush();
                return redirect()->route('login')
                    ->with('message', 'Session expired due to inactivity');
            }
            
            session(['last_activity' => time()]);
        }
        
        return $next($request);
    }
}
```

### Authorization

#### Role-Based Access Control (RBAC)

```php
// database/migrations/create_roles_and_permissions.php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('description')->nullable();
    $table->timestamps();
});

Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('description')->nullable();
    $table->timestamps();
});

Schema::create('role_user', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->primary(['role_id', 'user_id']);
});

Schema::create('permission_role', function (Blueprint $table) {
    $table->foreignId('permission_id')->constrained()->onDelete('cascade');
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->primary(['permission_id', 'role_id']);
});
```

Implement authorization:

```php
// app/Policies/DomainPolicy.php
namespace App\Policies;

class DomainPolicy
{
    public function view(User $user, Domain $domain)
    {
        return $user->id === $domain->user_id 
            || $user->hasRole('admin');
    }
    
    public function update(User $user, Domain $domain)
    {
        return $user->id === $domain->user_id 
            || $user->hasPermission('domains.update.any');
    }
    
    public function delete(User $user, Domain $domain)
    {
        return $user->hasPermission('domains.delete');
    }
}
```

#### API Authentication

Using Laravel Sanctum:

```bash
docker compose exec app composer require laravel/sanctum
docker compose exec app php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
docker compose exec app php artisan migrate
```

Configure rate limiting:

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:60,1', // 60 requests per minute
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::get('/domains', [DomainController::class, 'index']);
});
```

## Data Encryption

### Database Encryption

#### Encrypt Sensitive Fields

```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $casts = [
        'two_factor_secret' => 'encrypted',
        'api_key' => 'encrypted',
    ];
    
    // For more sensitive data, use explicit encryption
    public function setRegistrarPasswordAttribute($value)
    {
        $this->attributes['registrar_password'] = encrypt($value);
    }
    
    public function getRegistrarPasswordAttribute($value)
    {
        return decrypt($value);
    }
}
```

#### Database-Level Encryption

For PostgreSQL, enable pgcrypto:

```sql
-- Enable extension
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Encrypt column
UPDATE users 
SET encrypted_field = pgp_sym_encrypt(plaintext_field, 'encryption-key');

-- Decrypt column
SELECT pgp_sym_decrypt(encrypted_field::bytea, 'encryption-key') 
FROM users;
```

### Transmission Encryption

#### Force HTTPS

Configure in `app/Providers/AppServiceProvider.php`:

```php
public function boot()
{
    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
```

Configure nginx:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS server
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    ssl_certificate /etc/nginx/ssl/certificate.crt;
    ssl_certificate_key /etc/nginx/ssl/private.key;
    
    # Strong SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
}
```

### Storage Encryption

```php
// Encrypt files before storage
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

// Store encrypted
$encrypted = Crypt::encryptString($fileContent);
Storage::put('documents/sensitive.enc', $encrypted);

// Retrieve and decrypt
$encrypted = Storage::get('documents/sensitive.enc');
$decrypted = Crypt::decryptString($encrypted);
```

## Audit Logging

### Activity Logging

Install activity log package:

```bash
docker compose exec app composer require spatie/laravel-activitylog
docker compose exec app php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
docker compose exec app php artisan migrate
```

Implement logging:

```php
// app/Models/Domain.php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Domain extends Model
{
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'expires_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

// Manual logging
activity()
    ->causedBy(auth()->user())
    ->performedOn($domain)
    ->withProperties(['ip' => request()->ip()])
    ->log('Domain transferred');
```

### Security Event Logging

```php
// app/Listeners/LogSecurityEvent.php
namespace App\Listeners;

class LogSecurityEvent
{
    public function handle($event)
    {
        Log::channel('security')->info('Security Event', [
            'event' => class_basename($event),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $event->getData(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

// Events to log
protected $listen = [
    'Illuminate\Auth\Events\Login' => [LogSecurityEvent::class],
    'Illuminate\Auth\Events\Logout' => [LogSecurityEvent::class],
    'Illuminate\Auth\Events\Failed' => [LogSecurityEvent::class],
    'Illuminate\Auth\Events\Lockout' => [LogSecurityEvent::class],
    'App\Events\DomainTransferred' => [LogSecurityEvent::class],
    'App\Events\PasswordChanged' => [LogSecurityEvent::class],
];
```

Configure separate security log channel in `config/logging.php`:

```php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'info',
        'days' => 90, // Keep for 90 days
    ],
],
```

### Compliance Logging

```php
// Log for compliance (GDPR, etc.)
activity()
    ->causedBy(auth()->user())
    ->withProperties([
        'action' => 'data_export',
        'data_type' => 'user_data',
        'ip' => request()->ip(),
        'reason' => $request->reason,
    ])
    ->log('User data exported');
```

## OWASP Top 10 Protection

### 1. Broken Access Control

```php
// Always use authorization
public function update(Request $request, Domain $domain)
{
    $this->authorize('update', $domain);
    // Update logic
}

// Use policies consistently
Gate::define('transfer-domain', function (User $user, Domain $domain) {
    return $user->id === $domain->user_id 
        && $domain->status === 'active';
});
```

### 2. Cryptographic Failures

```php
// Use Laravel's encryption
$encrypted = encrypt($sensitive);
$decrypted = decrypt($encrypted);

// Hash passwords properly (Laravel does this by default)
Hash::make($password);

// Store API keys securely
'api_key' => 'encrypted:' . encrypt($apiKey)
```

### 3. Injection

```php
// Use Eloquent ORM (parameterized queries)
Domain::where('user_id', $userId)->get();

// Never concatenate user input in queries
// BAD: DB::select("SELECT * FROM domains WHERE name = '$name'");
// GOOD:
DB::select('SELECT * FROM domains WHERE name = ?', [$name]);

// Validate and sanitize input
$validated = $request->validate([
    'domain' => 'required|string|max:255|regex:/^[a-zA-Z0-9\-\.]+$/',
]);
```

### 4. Insecure Design

```php
// Implement rate limiting
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/api/domain/transfer', [DomainController::class, 'transfer']);
});

// Use CSRF protection (enabled by default)
// In forms: @csrf

// Implement proper session management
config(['session.lifetime' => 120]);
```

### 5. Security Misconfiguration

```bash
# Production configuration
APP_DEBUG=false
APP_ENV=production

# Disable unnecessary features
# Remove unused routes
# Disable directory listing in nginx
# Hide server version

# Regular security audits
docker compose exec app php artisan config:cache
docker compose exec app composer audit
```

### 6. Vulnerable Components

```bash
# Regular dependency updates
docker compose exec app composer update
docker compose exec app composer audit

# Security advisories check
docker compose exec app composer require --dev roave/security-advisories:dev-latest

# Monitor for CVEs
docker scan domaindesk:latest

# Automated dependency updates with Dependabot
# .github/dependabot.yml
```

### 7. Identification & Authentication Failures

```php
// Strong password policy (see Authentication section)
// Implement 2FA
// Use secure session management
// Implement account lockout

public function login(Request $request)
{
    $this->validateLogin($request);
    
    if ($this->hasTooManyLoginAttempts($request)) {
        $this->fireLockoutEvent($request);
        return $this->sendLockoutResponse($request);
    }
    
    if ($this->attemptLogin($request)) {
        return $this->sendLoginResponse($request);
    }
    
    $this->incrementLoginAttempts($request);
    return $this->sendFailedLoginResponse($request);
}
```

### 8. Software & Data Integrity Failures

```php
// Verify package integrity
// Use composer.lock and package-lock.json

// Implement code signing
// Use trusted repositories only

// Validate uploaded files
$request->validate([
    'file' => 'required|file|mimes:pdf,doc,docx|max:2048',
]);

// Scan uploaded files
use Illuminate\Support\Facades\Storage;

$file = $request->file('document');
$path = $file->store('temp');

// Scan with antivirus
$safe = $this->scanFile(Storage::path($path));

if (!$safe) {
    Storage::delete($path);
    abort(422, 'Potentially malicious file detected');
}
```

### 9. Security Logging & Monitoring Failures

```php
// Comprehensive logging (see Audit Logging section)

// Monitor failed login attempts
Event::listen(Failed::class, function ($event) {
    Log::channel('security')->warning('Failed login attempt', [
        'email' => $event->credentials['email'],
        'ip' => request()->ip(),
    ]);
    
    // Alert on multiple failures
    $attempts = Cache::increment('login_failures:' . request()->ip());
    if ($attempts > 5) {
        // Send alert to security team
    }
});

// Real-time alerting
if ($criticalSecurityEvent) {
    Notification::send($securityTeam, new SecurityAlert($details));
}
```

### 10. Server-Side Request Forgery (SSRF)

```php
// Validate URLs
use Illuminate\Support\Facades\Validator;

$validator = Validator::make(['url' => $url], [
    'url' => 'required|url|active_url',
]);

// Whitelist allowed domains
$allowedDomains = ['api.resellerclub.com', 'api.namecheap.com'];
$host = parse_url($url, PHP_URL_HOST);

if (!in_array($host, $allowedDomains)) {
    abort(403, 'Domain not allowed');
}

// Use Guzzle with timeout and restrictions
$client = new \GuzzleHttp\Client([
    'timeout' => 5,
    'allow_redirects' => false,
]);

// Block private IP ranges
$ip = gethostbyname($host);
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
    abort(403, 'Private IP ranges not allowed');
}
```

## Vulnerability Management

### Vulnerability Scanning

```bash
# Scan dependencies
docker compose exec app composer audit

# Scan Docker images
docker scan domaindesk:latest

# SAST (Static Application Security Testing)
docker run --rm -v $(pwd):/app phpstan/phpstan analyse /app --level=8

# Dependency check
docker run --rm -v $(pwd):/src owasp/dependency-check \
  --project "DomainDesk" \
  --scan /src \
  --format HTML \
  --out /src/reports
```

### Patch Management

```bash
# Create patch management schedule

# Weekly: Review security advisories
composer show --direct | xargs composer outdated

# Monthly: Apply non-breaking updates
composer update

# Quarterly: Major version updates
# Review changelog and test thoroughly before applying

# Emergency: Critical security patches
# Apply immediately and deploy
```

### Security Testing

```bash
# Run security tests
docker compose exec app php artisan test --filter Security

# Example security test
// tests/Feature/SecurityTest.php
public function test_csrf_protection()
{
    $response = $this->post('/api/domain', []);
    $response->assertStatus(419);
}

public function test_authentication_required()
{
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
}

public function test_sql_injection_prevention()
{
    $response = $this->get('/domains?search=' . urlencode("'; DROP TABLE domains; --"));
    $response->assertStatus(200);
    $this->assertDatabaseHas('domains', ['id' => 1]);
}
```

## Security Headers

### Configure Security Headers

In `docker/nginx/nginx-prod.conf`:

```nginx
# Prevent clickjacking
add_header X-Frame-Options "SAMEORIGIN" always;

# Prevent MIME type sniffing
add_header X-Content-Type-Options "nosniff" always;

# Enable XSS protection
add_header X-XSS-Protection "1; mode=block" always;

# HSTS (HTTP Strict Transport Security)
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;

# Content Security Policy
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self';" always;

# Referrer Policy
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# Permissions Policy
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

### Laravel Security Middleware

```php
// app/Http/Middleware/SecurityHeaders.php
namespace App\Http\Middleware;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        return $response;
    }
}

// Register in app/Http/Kernel.php
protected $middleware = [
    \App\Http\Middleware\SecurityHeaders::class,
];
```

### Test Security Headers

```bash
# Check headers
curl -I https://yourdomain.com

# Use security header analyzer
curl -s https://yourdomain.com | \
  docker run --rm -i ghcr.io/mozilla/http-observatory-cli analyze
```

## Incident Response Plan

### Preparation

1. **Security Team**
   - Designate security lead
   - Define roles and responsibilities
   - Maintain contact list

2. **Detection Systems**
   - Implement monitoring and alerting
   - Configure intrusion detection
   - Set up log aggregation

3. **Response Tools**
   - Incident response playbooks
   - Communication channels
   - Forensics tools

### Detection & Analysis

```bash
# Check for suspicious activity
docker compose logs app | grep -E "(attack|injection|unauthorized)"

# Review failed login attempts
docker compose exec app php artisan tinker
>>> Activity::where('description', 'Failed login')->count()

# Check for unusual database activity
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "SELECT * FROM pg_stat_activity WHERE state != 'idle';"

# Monitor for data exfiltration
docker compose logs --since 1h app | grep "large_response"
```

### Containment

```bash
# Immediate containment
# 1. Enable maintenance mode
docker compose exec app php artisan down

# 2. Block suspicious IPs
sudo ufw deny from 1.2.3.4 to any port 443

# 3. Disable compromised accounts
docker compose exec app php artisan tinker
>>> User::where('email', 'compromised@example.com')->update(['status' => 'suspended'])

# 4. Rotate credentials
docker compose exec app php artisan key:generate
# Update database passwords
# Rotate API keys
```

### Eradication

```bash
# Remove malware/backdoors
find . -name "*.php" -type f -mtime -1 -ls

# Restore from clean backup
./scripts/restore-backup.sh 2024-01-15

# Update all dependencies
docker compose exec app composer update
docker compose up -d --build

# Patch vulnerabilities
git pull origin security-patch
```

### Recovery

```bash
# Gradual restoration
# 1. Verify system integrity
docker compose exec app php artisan optimize
docker compose exec app php artisan test

# 2. Restore services incrementally
docker compose up -d postgres redis
# Wait and verify
docker compose up -d app
# Wait and verify
docker compose up -d queue

# 3. Monitor closely
docker compose logs -f

# 4. Exit maintenance mode
docker compose exec app php artisan up
```

### Post-Incident

1. **Document Incident**
   - Timeline of events
   - Actions taken
   - Impact assessment
   - Root cause analysis

2. **Lessons Learned**
   - What worked well
   - What could be improved
   - Preventive measures

3. **Implement Improvements**
   - Update security controls
   - Enhance monitoring
   - Update runbooks
   - Train team

## Penetration Testing Guidelines

### Scope Definition

```markdown
# Penetration Test Scope

## In Scope
- Web application (https://yourdomain.com)
- API endpoints (/api/*)
- Authentication system
- Authorization controls
- Input validation

## Out of Scope
- Physical security
- Social engineering
- Denial of Service attacks
- Third-party services
- Production database

## Testing Window
- Date: 2024-02-01 to 2024-02-03
- Time: 09:00 - 17:00 UTC
- Environment: Staging
```

### Testing Checklist

#### Authentication Testing

```bash
# Test login brute force protection
for i in {1..10}; do
    curl -X POST https://yourdomain.com/login \
      -d "email=test@example.com&password=wrong$i"
done

# Test session fixation
# Test password reset flow
# Test 2FA bypass attempts
```

#### Authorization Testing

```bash
# Test horizontal privilege escalation
curl -X GET https://yourdomain.com/api/domains/1 \
  -H "Authorization: Bearer user2_token"

# Test vertical privilege escalation
curl -X POST https://yourdomain.com/admin/users \
  -H "Authorization: Bearer user_token"

# Test IDOR (Insecure Direct Object References)
for i in {1..100}; do
    curl -X GET https://yourdomain.com/api/domains/$i \
      -H "Authorization: Bearer token"
done
```

#### Input Validation Testing

```bash
# SQL injection
curl "https://yourdomain.com/domains?search=' OR '1'='1"

# XSS
curl -X POST https://yourdomain.com/api/domains \
  -d "name=<script>alert('XSS')</script>.com"

# Command injection
curl -X POST https://yourdomain.com/api/whois \
  -d "domain=example.com; cat /etc/passwd"

# XXE
curl -X POST https://yourdomain.com/api/import \
  -d '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><foo>&xxe;</foo>'
```

#### API Security Testing

```bash
# Test rate limiting
for i in {1..100}; do
    curl -X GET https://yourdomain.com/api/domains &
done

# Test API authentication
curl -X GET https://yourdomain.com/api/domains

# Test API versioning security
curl -X GET https://yourdomain.com/api/v1/domains
curl -X GET https://yourdomain.com/api/v2/domains
```

### Automated Scanning

```bash
# OWASP ZAP
docker run -t owasp/zap2docker-stable zap-baseline.py \
  -t https://yourdomain.com

# Nikto
docker run --rm sullo/nikto -h https://yourdomain.com

# Nmap
nmap -sV -sC yourdomain.com

# SSLyze
docker run --rm nablac0d3/sslyze yourdomain.com
```

### Reporting

```markdown
# Penetration Test Report

## Executive Summary
- Test date: 2024-02-01
- Findings: 3 High, 5 Medium, 10 Low
- Overall risk: Medium

## Findings

### Critical: SQL Injection in Search
- Location: /api/domains/search
- Impact: Database compromise
- Recommendation: Use parameterized queries

### High: Broken Access Control
- Location: /api/domains/{id}
- Impact: Unauthorized data access
- Recommendation: Implement proper authorization checks

## Remediation Timeline
- Critical: 24 hours
- High: 1 week
- Medium: 1 month
- Low: Next release
```

## Security Compliance

### GDPR Compliance

```php
// Implement data export
public function export(Request $request)
{
    $user = auth()->user();
    $data = [
        'personal_data' => $user->toArray(),
        'domains' => $user->domains()->get(),
        'activity_log' => Activity::forSubject($user)->get(),
    ];
    
    activity()
        ->causedBy($user)
        ->log('Personal data exported');
    
    return response()->json($data);
}

// Implement right to be forgotten
public function delete(Request $request)
{
    $user = auth()->user();
    
    activity()
        ->causedBy($user)
        ->log('Account deletion requested');
    
    // Anonymize data
    $user->update([
        'name' => 'Deleted User',
        'email' => 'deleted_' . $user->id . '@example.com',
        'deleted_at' => now(),
    ]);
    
    auth()->logout();
    return redirect('/');
}
```

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [CWE Top 25](https://cwe.mitre.org/top25/)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)
- [PCI DSS Compliance](https://www.pcisecuritystandards.org/)
