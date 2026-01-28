# Phase 9: Automation & Background Jobs - Quick Start Guide

## For Developers

### Setup

1. **Install Dependencies** (if not already done):
```bash
composer install
```

2. **Configure Environment**:
```bash
# Queue Configuration
QUEUE_CONNECTION=database  # or redis for production
DB_QUEUE_TABLE=jobs
DB_QUEUE_RETRY_AFTER=180

# Email Settings
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

# Domain Settings (optional, defaults provided)
DEFAULT_RENEWAL_PRICE=15.00
LOW_BALANCE_THRESHOLD=100.00
AUTO_RENEW_DAYS_BEFORE_EXPIRY=7
```

3. **Run Migrations** (if not done):
```bash
php artisan migrate
```

4. **Create Queue Tables** (if using database queue):
```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

### Running Locally

#### Start Queue Worker
```bash
# In a separate terminal
php artisan queue:work --verbose
```

#### Run Scheduler (Development)
```bash
# In a separate terminal - runs every minute
php artisan schedule:work
```

Or manually trigger commands:
```bash
php artisan domains:scan-expiring
php artisan domains:process-auto-renewals
php artisan domains:send-renewal-reminders
php artisan partners:send-low-balance-alerts
```

### Testing

#### Run All Phase 9 Tests
```bash
php artisan test tests/Feature/Commands/ tests/Feature/Jobs/ tests/Feature/Notifications/
```

#### Run Specific Test Suites
```bash
# Commands only
php artisan test tests/Feature/Commands/

# Jobs only
php artisan test tests/Feature/Jobs/

# Notifications only
php artisan test tests/Feature/Notifications/

# Specific test file
php artisan test tests/Feature/Commands/ScanExpiringDomainsTest
```

#### Test with Coverage
```bash
php artisan test --coverage --min=80
```

### Development Workflow

#### Adding a New Command
1. Create command:
```bash
php artisan make:command MyNewCommand
```

2. Implement handle() method
3. Add to `app/Console/Kernel.php` schedule()
4. Create test file
5. Run tests

#### Adding a New Job
1. Create job:
```bash
php artisan make:job MyNewJob
```

2. Implement handle() method
3. Add retry logic ($tries, $timeout)
4. Create test file
5. Run tests

#### Adding a New Notification
1. Create notification:
```bash
php artisan make:notification MyNewNotification
```

2. Implement via(), toMail(), toArray() methods
3. Add to appropriate trigger points
4. Create test file
5. Run tests

### Common Tasks

#### Monitor Queue
```bash
# See queued jobs
php artisan queue:monitor

# See failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Flush failed jobs
php artisan queue:flush
```

#### Test Email Locally
```bash
# Use Mailhog (if installed)
# View emails at http://localhost:8025

# Or use Laravel's mail trap in .env
MAIL_MAILER=log
```

#### Debug Commands
```bash
# Run with verbose output
php artisan domains:scan-expiring -v

# Test notification sending
php artisan tinker
>>> $user = App\Models\User::first();
>>> $domain = App\Models\Domain::first();
>>> $user->notify(new App\Notifications\DomainExpiryAlert($domain, 30));
```

### Troubleshooting

#### Queue Not Processing
```bash
# Restart queue worker
php artisan queue:restart

# Clear cache
php artisan cache:clear
php artisan config:clear
```

#### Scheduler Not Running
```bash
# Manually run schedule
php artisan schedule:run

# Check scheduled commands
php artisan schedule:list

# Verify cron is set up (production)
crontab -l
```

#### Jobs Failing
```bash
# View failed jobs
php artisan queue:failed

# View logs
tail -f storage/logs/laravel.log

# Test job manually
php artisan tinker
>>> App\Jobs\ProcessDomainRenewalJob::dispatch($domain);
```

#### Notifications Not Sending
```bash
# Check mail configuration
php artisan tinker
>>> config('mail');

# Test email directly
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });

# Check notification table
php artisan tinker
>>> DB::table('notifications')->count();
```

### Best Practices

1. **Always Test Before Committing**
   - Run relevant test suites
   - Verify queue processing
   - Check logs for errors

2. **Use Factories for Testing**
   - Create realistic test data
   - Use factories instead of manual creation
   - Clean up after tests

3. **Log Important Events**
   - Use Log facade appropriately
   - Include context in logs
   - Monitor logs regularly

4. **Handle Errors Gracefully**
   - Implement try-catch blocks
   - Provide meaningful error messages
   - Send failure notifications when needed

5. **Keep Jobs Small**
   - Single responsibility principle
   - Break large tasks into smaller jobs
   - Chain jobs when needed

### Useful Commands Reference

```bash
# Queue
php artisan queue:work                    # Start worker
php artisan queue:work --queue=high,low   # Specific queues
php artisan queue:restart                 # Restart workers
php artisan queue:monitor                 # Monitor queue
php artisan queue:failed                  # List failed jobs
php artisan queue:retry all               # Retry all failed

# Scheduler
php artisan schedule:work                 # Run scheduler (dev)
php artisan schedule:run                  # Run once
php artisan schedule:list                 # List scheduled tasks
php artisan schedule:test                 # Test scheduling

# Testing
php artisan test                          # All tests
php artisan test --filter=ScanExpiring    # Specific test
php artisan test --parallel               # Parallel execution
php artisan test --coverage               # With coverage

# Cache
php artisan cache:clear                   # Clear cache
php artisan config:clear                  # Clear config
php artisan view:clear                    # Clear views
php artisan route:clear                   # Clear routes

# Database
php artisan migrate                       # Run migrations
php artisan migrate:fresh --seed          # Fresh with seeds
php artisan db:seed                       # Seed database
```

### Production Deployment Checklist

- [ ] Set QUEUE_CONNECTION=redis or database
- [ ] Configure Supervisor for queue workers
- [ ] Set up cron for scheduler
- [ ] Configure email SMTP settings
- [ ] Set appropriate timeouts
- [ ] Enable log rotation
- [ ] Set up monitoring/alerting
- [ ] Test all scheduled commands
- [ ] Verify queue processing
- [ ] Check notification delivery
- [ ] Review failed jobs regularly

### Need Help?

- Check Laravel documentation: https://laravel.com/docs
- Review test files for usage examples
- Check PHASE_9_AUTOMATION_SUMMARY.md for detailed info
- Ask team members familiar with the codebase
