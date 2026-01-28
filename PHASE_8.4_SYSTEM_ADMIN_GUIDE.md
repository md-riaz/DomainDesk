# Phase 8.4 - System Administration Guide

## Overview

Phase 8.4 implements comprehensive system administration interfaces for SuperAdmins to manage and monitor the DomainDesk platform.

## Features Implemented

### 1. Audit Log Viewer (`/admin/system/audit-logs`)

**Purpose**: Complete audit trail of all system activities

**Key Features**:
- Advanced filtering by user, partner, action, model type, role, and date range
- Real-time search across multiple fields
- Pagination with configurable items per page (25/50/100)
- CSV export with CSV injection prevention
- Detailed view modal showing:
  - Full change history (before/after comparison)
  - Related logs from same session
  - User agent and IP information
- Auto-refresh toggle (30-second intervals)

**Usage**:
```php
// Access filtered logs
Route: GET /admin/system/audit-logs?search=user@example.com&filterAction=created

// Export to CSV
Click "Export CSV" button - respects current filters
```

### 2. System Settings (`/admin/system/settings`)

**Purpose**: Configure system-wide settings across multiple categories

**Categories**:

#### General Settings
- Site Name
- Admin Email
- Default Timezone
- Default Currency (USD, EUR, GBP, etc.)
- Date/Time Format

#### Email Settings
- SMTP Host, Port, Username
- SMTP Password (encrypted)
- SMTP Encryption (TLS/SSL)
- From Address and Name
- Test Email Functionality

#### Domain Settings
- Default Nameservers (1-4)
- Default TTL
- Auto-Renewal Lead Time (days)
- Grace Period (days)

#### Billing Settings
- Currency Symbol
- Tax Rate (%)
- Invoice Prefix
- Low Balance Threshold

**Usage**:
```php
// Get a setting
$siteName = Setting::get('site_name', 'Default Name');

// Set a setting
Setting::set('smtp_host', 'smtp.gmail.com', 'string', 'email');

// Set encrypted setting
Setting::set('smtp_password', 'secret', 'encrypted', 'email');

// Get settings by group
$emailSettings = Setting::getByGroup('email');

// Clear settings cache
Setting::clearCache();
```

### 3. Maintenance Mode (`/admin/system/maintenance`)

**Purpose**: Enable/disable site-wide maintenance mode

**Features**:
- Toggle maintenance mode on/off
- Custom maintenance message
- IP whitelist for allowed access
- Preview maintenance page
- Audit logging of all changes

**Usage**:
```bash
# Enable via artisan (alternative)
php artisan down --secret="your-secret"

# Disable via artisan
php artisan up
```

### 4. System Health (`/admin/system/health`)

**Purpose**: Monitor system component health and status

**Health Checks**:
- **Database**: Connection test and version info
- **Cache**: Read/write test and driver info
- **Queue**: Pending/failed job counts
- **Storage**: Disk usage and write permissions
- **Mail**: Configuration validation

**System Information**:
- Laravel Version
- PHP Version
- Server Software
- Environment
- Memory Limit
- Cache/Queue/Session Drivers
- Database Driver

**Status Indicators**:
- 🟢 **OK**: Component working properly
- 🟡 **Warning**: Component working but needs attention
- 🔴 **Error**: Component not working

### 5. Cache Management (`/admin/system/cache`)

**Purpose**: Manage application caches for performance

**Cache Types**:
- **Application Cache**: User data and settings cache
- **Config Cache**: Cached configuration files
- **Route Cache**: Compiled route definitions
- **View Cache**: Compiled Blade templates
- **Event Cache**: Cached event listeners

**Operations**:
- Clear All Caches
- Clear Specific Cache
- Optimize Cache (rebuild all caches)

**Usage**:
```bash
# Via artisan commands
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Security Features

### Access Control
- All routes restricted to `super_admin` role
- Middleware: `auth` + `role:super_admin`

### Audit Logging
- All setting changes logged
- Maintenance mode toggles logged
- Cache operations logged
- CSV exports logged

### Data Protection
- Sensitive settings encrypted (SMTP passwords)
- CSV injection prevention on exports
- Rate limiting on sensitive operations
- CSRF protection on all forms

### Input Validation
- Email format validation
- Numeric range validation (ports, rates, days)
- Required field validation
- Type validation (integer, float, string)

## Database Schema

### Settings Table
```sql
CREATE TABLE settings (
    id BIGINT PRIMARY KEY,
    key VARCHAR(255) UNIQUE,
    value TEXT,
    type VARCHAR(255) DEFAULT 'string',  -- string, integer, boolean, json, encrypted
    `group` VARCHAR(255) DEFAULT 'general',  -- general, email, domain, billing, system
    description TEXT,
    timestamps
);
```

## API Reference

### Setting Model

```php
// Get setting with default
$value = Setting::get('key', 'default');

// Set setting
Setting::set('key', 'value', 'string', 'general');

// Get by group
$settings = Setting::getByGroup('email');

// Clear cache
Setting::clearCache();
```

### AuditLog Queries

```php
// Get recent logs
$logs = AuditLog::latest()->paginate(50);

// Filter by user
$logs = AuditLog::where('user_id', $userId)->get();

// Filter by action
$logs = AuditLog::where('action', 'created')->get();

// Filter by model
$logs = AuditLog::where('auditable_type', Partner::class)->get();

// Date range
$logs = AuditLog::whereBetween('created_at', [$from, $to])->get();
```

## Testing

### Test Coverage
- 37 comprehensive tests
- 100+ assertions
- Coverage for all major features

### Running Tests
```bash
# All system admin tests
php artisan test --filter="AuditLogsTest|SystemSettingsTest|SystemHealthTest"

# Specific component
php artisan test --filter=AuditLogsTest
php artisan test --filter=SystemSettingsTest
php artisan test --filter=SystemHealthTest
```

## Configuration

### Environment Variables
```env
# Email Settings (defaults)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Cache Settings
CACHE_DRIVER=file
CACHE_PREFIX=domaindesk_cache

# Queue Settings
QUEUE_CONNECTION=database
```

### Default Settings
Can be seeded with:
```php
Setting::set('site_name', 'DomainDesk', 'string', 'general');
Setting::set('admin_email', 'admin@example.com', 'string', 'general');
Setting::set('default_timezone', 'UTC', 'string', 'general');
Setting::set('default_currency', 'USD', 'string', 'general');
// ... etc
```

## Troubleshooting

### Settings Not Saving
- Check audit logs for error messages
- Verify SuperAdmin role access
- Clear cache: `php artisan cache:clear`

### Maintenance Mode Issues
- Check file permissions on `storage/framework/down`
- Verify Laravel version supports `--secret` flag
- Use artisan commands as fallback

### Health Check Failures
- Follow troubleshooting tips in the Health dashboard
- Check `.env` configuration
- Verify service connectivity (database, cache, etc.)

### Cache Not Clearing
- Check storage directory permissions
- Verify cache driver is writable
- Use `php artisan optimize:clear` for full reset

## Best Practices

1. **Regular Monitoring**: Check System Health daily
2. **Backup Settings**: Export settings before major changes
3. **Review Audit Logs**: Weekly review for security
4. **Cache Management**: Clear cache after deployments
5. **Maintenance Planning**: Schedule maintenance during low traffic
6. **Test Email**: Verify SMTP settings after changes
7. **Secure Passwords**: Use strong SMTP passwords
8. **Monitor Disk Space**: Keep storage below 80%

## Next Steps

Consider implementing:
- Settings import/export functionality
- Advanced audit log analytics
- Scheduled health check reports
- Automatic cache optimization
- Multi-admin notifications
- Settings versioning/rollback

## Support

For issues or questions:
1. Check audit logs for error details
2. Review system health dashboard
3. Check Laravel logs: `storage/logs/laravel.log`
4. Contact system administrator
