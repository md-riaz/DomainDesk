# Installation Guide

Complete installation instructions for DomainDesk - a White-Label Domain Reseller & Client Billing Platform.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Development Installation](#development-installation)
3. [Production Installation](#production-installation)
4. [Database Setup](#database-setup)
5. [Environment Configuration](#environment-configuration)
6. [Initial Setup](#initial-setup)
7. [Verification](#verification)
8. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements

- **PHP**: 8.2 or higher
- **Composer**: 2.x
- **Node.js**: 18.x or higher
- **NPM**: 9.x or higher
- **Database**: SQLite 3.x (development) or PostgreSQL 13+ (production)
- **Redis**: 6.x or higher (production)
- **Memory**: 512MB RAM (minimum), 2GB+ recommended
- **Disk Space**: 1GB minimum

### PHP Extensions Required

```bash
php -m | grep -E 'pdo|sqlite|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|curl|fileinfo'
```

Required extensions:
- PDO
- SQLite3 (dev) / pdo_pgsql (prod)
- mbstring
- OpenSSL
- Tokenizer
- XML
- ctype
- JSON
- BCMath
- cURL
- Fileinfo
- Redis (production)

---

## Development Installation

### Quick Start (Recommended)

```bash
# Clone the repository
git clone https://github.com/md-riaz/DomainDesk.git
cd DomainDesk

# Run the automated setup
composer setup

# Start the development server
composer dev
```

The `composer dev` command starts all services concurrently:
- Laravel development server (http://localhost:8000)
- Queue worker
- Log viewer (Pail)
- Vite asset compiler

### Manual Installation

If you prefer step-by-step installation:

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment file
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Run database migrations
php artisan migrate

# 5. Seed the database (optional)
php artisan db:seed

# 6. Install Node dependencies
npm install

# 7. Build frontend assets
npm run build

# 8. Start the development server
php artisan serve
```

### Development with Laravel Sail (Docker)

```bash
# Install Laravel Sail
composer require laravel/sail --dev

# Publish Sail configuration
php artisan sail:install

# Start Sail containers
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate

# Build assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

---

## Production Installation

### Prerequisites

- Ubuntu 20.04+ or Debian 11+ server
- Root or sudo access
- Domain name pointing to your server
- SSL certificate (Let's Encrypt recommended)

### Server Setup

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-pgsql php8.2-sqlite3 php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-bcmath php8.2-redis php8.2-zip php8.2-gd

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### Application Deployment

```bash
# Create application directory
sudo mkdir -p /var/www/domaindesk
sudo chown $USER:$USER /var/www/domaindesk

# Clone repository
cd /var/www/domaindesk
git clone https://github.com/md-riaz/DomainDesk.git .

# Install dependencies (production)
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm ci

# Build production assets
npm run build

# Set permissions
sudo chown -R www-data:www-data /var/www/domaindesk
sudo chmod -R 755 /var/www/domaindesk
sudo chmod -R 775 /var/www/domaindesk/storage
sudo chmod -R 775 /var/www/domaindesk/bootstrap/cache
```

---

## Database Setup

### Development (SQLite)

SQLite is pre-configured and ready to use:

```bash
# Database file is automatically created
php artisan migrate

# Seed sample data (optional)
php artisan db:seed
```

### Production (PostgreSQL)

```bash
# Create PostgreSQL user and database
sudo -u postgres psql << EOF
CREATE USER domaindesk WITH PASSWORD 'your_secure_password';
CREATE DATABASE domaindesk OWNER domaindesk;
GRANT ALL PRIVILEGES ON DATABASE domaindesk TO domaindesk;
\q
EOF

# Update .env file
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=domaindesk
DB_USERNAME=domaindesk
DB_PASSWORD=your_secure_password

# Run migrations
php artisan migrate --force
```

### Database Backup Setup

```bash
# PostgreSQL backup script
cat > /usr/local/bin/backup-domaindesk.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/domaindesk"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
pg_dump -U domaindesk domaindesk | gzip > $BACKUP_DIR/domaindesk_$DATE.sql.gz
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
EOF

chmod +x /usr/local/bin/backup-domaindesk.sh

# Add to crontab (daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-domaindesk.sh") | crontab -
```

---

## Environment Configuration

### Essential Environment Variables

Copy and configure `.env.production.example`:

```bash
cp .env.production.example .env
php artisan key:generate
```

### Required Configuration

```ini
# Application
APP_NAME="DomainDesk"
APP_ENV=production
APP_KEY=base64:... # Generated by php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=domaindesk
DB_USERNAME=domaindesk
DB_PASSWORD=your_secure_password

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (Example with SendGrid)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Registrar (ResellerClub/LogicBoxes)
RESELLERCLUB_API_URL=https://httpapi.com/api
RESELLERCLUB_TEST_MODE=false
RESELLERCLUB_AUTH_USERID=your_reseller_id
RESELLERCLUB_API_KEY=your_api_key
RESELLERCLUB_NS1=ns1.yourdomain.com
RESELLERCLUB_NS2=ns2.yourdomain.com
```

### Optional Configuration

```ini
# AWS S3 (for file storage)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=domaindesk-production

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning
LOG_STACK=daily

# Performance
BCRYPT_ROUNDS=12
```

---

## Initial Setup

### Create Super Admin Account

```bash
# Run the seeder to create default admin
php artisan db:seed --class=AdminUserSeeder

# Or create manually via tinker
php artisan tinker
>>> $user = App\Models\User::create([
...   'name' => 'Admin',
...   'email' => 'admin@domaindesk.com',
...   'password' => bcrypt('SecurePassword123'),
...   'role' => 'super_admin',
... ]);
>>> exit
```

### Configure Queue Workers (Production)

```bash
# Create systemd service for queue worker
sudo nano /etc/systemd/system/domaindesk-worker.service
```

Add the following configuration:

```ini
[Unit]
Description=DomainDesk Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/domaindesk
ExecStart=/usr/bin/php /var/www/domaindesk/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable domaindesk-worker
sudo systemctl start domaindesk-worker
sudo systemctl status domaindesk-worker
```

### Configure Scheduled Tasks

Add to crontab:

```bash
sudo crontab -e -u www-data
```

Add this line:

```cron
* * * * * cd /var/www/domaindesk && php artisan schedule:run >> /dev/null 2>&1
```

### Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/domaindesk
```

Basic Nginx configuration:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;
    root /var/www/domaindesk/public;

    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/domaindesk /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
```

---

## Verification

### Development Verification

```bash
# Check if server is running
curl http://localhost:8000

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# Run tests
php artisan test

# Check queue is working
php artisan queue:work --once
```

### Production Verification

```bash
# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check Nginx
sudo systemctl status nginx

# Check Redis
redis-cli ping

# Check PostgreSQL
sudo -u postgres psql -c "SELECT version();"

# Check queue worker
sudo systemctl status domaindesk-worker

# Check application
php artisan about

# Run health check
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting

### Common Issues

#### 1. Permission Errors

```bash
# Fix storage and cache permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 2. Database Connection Failed

```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Test connection
psql -U domaindesk -d domaindesk -h 127.0.0.1

# Check .env credentials
php artisan tinker
>>> config('database.connections.pgsql');
```

#### 3. Queue Not Processing

```bash
# Check worker status
sudo systemctl status domaindesk-worker

# View worker logs
sudo journalctl -u domaindesk-worker -f

# Restart worker
sudo systemctl restart domaindesk-worker
```

#### 4. Assets Not Loading

```bash
# Rebuild assets
npm run build

# Clear cache
php artisan optimize:clear

# Check Nginx configuration
sudo nginx -t
```

#### 5. Email Not Sending

```bash
# Test email configuration
php artisan tinker
>>> Mail::raw('Test email', function($msg) {
...     $msg->to('test@example.com')->subject('Test');
... });

# Check mail logs
tail -f storage/logs/laravel.log
```

### Performance Issues

```bash
# Enable OPcache
sudo nano /etc/php/8.2/fpm/php.ini
# Set: opcache.enable=1

# Optimize application
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Getting Help

- **Documentation**: https://github.com/md-riaz/DomainDesk/tree/main/docs
- **Issues**: https://github.com/md-riaz/DomainDesk/issues
- **Email**: support@domaindesk.com

---

## Next Steps

After installation is complete:

1. Read [USER_GUIDE.md](USER_GUIDE.md) for usage instructions
2. Review [SECURITY.md](SECURITY.md) for security best practices
3. Set up monitoring (see [OPERATIONS.md](OPERATIONS.md))
4. Configure backups (see [DEPLOYMENT.md](DEPLOYMENT.md))
5. Review [ARCHITECTURE.md](ARCHITECTURE.md) for system understanding

---

**Last Updated**: January 2025
