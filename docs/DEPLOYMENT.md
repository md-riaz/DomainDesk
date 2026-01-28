# Deployment Guide for DomainDesk

This guide covers production deployment of DomainDesk on Ubuntu/Debian servers.

## Table of Contents

- [Server Requirements](#server-requirements)
- [Server Setup](#server-setup)
- [SSL Certificate Setup](#ssl-certificate-setup)
- [Domain Configuration](#domain-configuration)
- [Queue Worker Setup](#queue-worker-setup)
- [Cron Job Configuration](#cron-job-configuration)
- [Monitoring Setup](#monitoring-setup)
- [Backup Strategy](#backup-strategy)
- [Disaster Recovery](#disaster-recovery)
- [Zero-Downtime Deployment](#zero-downtime-deployment)
- [Rollback Procedures](#rollback-procedures)

## Server Requirements

### Minimum Requirements

- **OS**: Ubuntu 22.04 LTS or Debian 11+
- **CPU**: 2 cores
- **RAM**: 4GB
- **Disk**: 40GB SSD
- **Network**: 100Mbps

### Recommended for Production

- **OS**: Ubuntu 22.04 LTS
- **CPU**: 4 cores
- **RAM**: 8GB
- **Disk**: 100GB SSD
- **Network**: 1Gbps

### Software Requirements

- Docker 24.0+
- Docker Compose 2.20+
- Git 2.34+
- Nginx (if not using Docker)
- PostgreSQL 15+ (if not using Docker)
- Redis 7+ (if not using Docker)

## Server Setup

### Initial Server Configuration

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential tools
sudo apt install -y curl git unzip software-properties-common \
  apt-transport-https ca-certificates gnupg lsb-release

# Set timezone
sudo timedatectl set-timezone UTC

# Configure firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Install Docker

```bash
# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
  sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Set up the stable repository
echo "deb [arch=$(dpkg --print-architecture) \
  signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] \
  https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Start and enable Docker
sudo systemctl enable docker
sudo systemctl start docker

# Add current user to docker group
sudo usermod -aG docker $USER
newgrp docker

# Verify installation
docker --version
docker compose version
```

### Create Deployment User

```bash
# Create deployment user
sudo useradd -m -s /bin/bash domaindesk
sudo usermod -aG docker domaindesk

# Set up SSH key authentication
sudo mkdir -p /home/domaindesk/.ssh
sudo touch /home/domaindesk/.ssh/authorized_keys
sudo chmod 700 /home/domaindesk/.ssh
sudo chmod 600 /home/domaindesk/.ssh/authorized_keys
sudo chown -R domaindesk:domaindesk /home/domaindesk/.ssh

# Add your public key
echo "your-ssh-public-key" | sudo tee -a /home/domaindesk/.ssh/authorized_keys
```

### Clone Repository

```bash
# Switch to deployment user
sudo su - domaindesk

# Clone repository
cd /home/domaindesk
git clone https://github.com/yourusername/DomainDesk.git
cd DomainDesk

# Checkout production branch
git checkout main
```

### Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
docker compose run --rm app php artisan key:generate

# Edit environment file
nano .env
```

Required environment variables:

```bash
APP_NAME=DomainDesk
APP_ENV=production
APP_KEY=base64:generated-key-here
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=domaindesk
DB_USERNAME=domaindesk
DB_PASSWORD=secure_random_password_here

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=secure_redis_password_here
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Registrar API credentials (if using ResellerClub)
RESELLERCLUB_USER_ID=your_user_id
RESELLERCLUB_API_KEY=your_api_key
RESELLERCLUB_TEST_MODE=false
```

### Initial Deployment

```bash
# Build and start containers
docker compose -f docker-compose.prod.yml up -d --build

# Run migrations
docker compose exec app php artisan migrate --force

# Seed initial data (if needed)
docker compose exec app php artisan db:seed --force

# Optimize application
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan optimize

# Link storage
docker compose exec app php artisan storage:link

# Verify deployment
docker compose ps
curl -I http://localhost
```

## SSL Certificate Setup

### Using Let's Encrypt with Certbot

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Stop nginx container temporarily
docker compose stop nginx

# Obtain certificate
sudo certbot certonly --standalone \
  -d yourdomain.com \
  -d www.yourdomain.com \
  --email admin@yourdomain.com \
  --agree-tos \
  --non-interactive

# Certificates will be saved to:
# /etc/letsencrypt/live/yourdomain.com/fullchain.pem
# /etc/letsencrypt/live/yourdomain.com/privkey.pem

# Copy certificates to project
sudo mkdir -p docker/nginx/ssl
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem \
  docker/nginx/ssl/certificate.crt
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem \
  docker/nginx/ssl/private.key
sudo chown -R domaindesk:domaindesk docker/nginx/ssl

# Set up auto-renewal
sudo certbot renew --dry-run

# Create renewal hook
sudo tee /etc/letsencrypt/renewal-hooks/deploy/domaindesk.sh << 'EOF'
#!/bin/bash
cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem \
  /home/domaindesk/DomainDesk/docker/nginx/ssl/certificate.crt
cp /etc/letsencrypt/live/yourdomain.com/privkey.pem \
  /home/domaindesk/DomainDesk/docker/nginx/ssl/private.key
docker compose -f /home/domaindesk/DomainDesk/docker-compose.prod.yml restart nginx
EOF

sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/domaindesk.sh
```

### Configure Nginx for SSL

Update `docker/nginx/nginx-prod.conf`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    ssl_certificate /etc/nginx/ssl/certificate.crt;
    ssl_certificate_key /etc/nginx/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Restart nginx:

```bash
docker compose -f docker-compose.prod.yml restart nginx
```

## Domain Configuration

### DNS Configuration

Add these DNS records at your domain registrar:

```
Type    Name    Value                   TTL
A       @       your.server.ip.address  3600
A       www     your.server.ip.address  3600
CNAME   *       yourdomain.com          3600
```

### Verify Domain Setup

```bash
# Check DNS propagation
dig yourdomain.com
dig www.yourdomain.com

# Test HTTP to HTTPS redirect
curl -I http://yourdomain.com

# Test HTTPS
curl -I https://yourdomain.com

# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com
```

## Queue Worker Setup

### Create Systemd Service

Create `/etc/systemd/system/domaindesk-queue.service`:

```ini
[Unit]
Description=DomainDesk Queue Worker
After=docker.service
Requires=docker.service

[Service]
Type=simple
User=domaindesk
Group=domaindesk
Restart=always
RestartSec=10
WorkingDirectory=/home/domaindesk/DomainDesk

ExecStart=/usr/bin/docker compose -f docker-compose.prod.yml up queue
ExecStop=/usr/bin/docker compose -f docker-compose.prod.yml stop queue

StandardOutput=append:/var/log/domaindesk/queue.log
StandardError=append:/var/log/domaindesk/queue-error.log

[Install]
WantedBy=multi-user.target
```

Configure and start:

```bash
# Create log directory
sudo mkdir -p /var/log/domaindesk
sudo chown domaindesk:domaindesk /var/log/domaindesk

# Reload systemd
sudo systemctl daemon-reload

# Enable and start service
sudo systemctl enable domaindesk-queue
sudo systemctl start domaindesk-queue

# Check status
sudo systemctl status domaindesk-queue

# View logs
sudo journalctl -u domaindesk-queue -f
```

### Multiple Queue Workers

For high-traffic sites, run multiple workers:

```bash
# Create additional worker services
for i in {1..3}; do
  sudo tee /etc/systemd/system/domaindesk-queue-${i}.service << EOF
[Unit]
Description=DomainDesk Queue Worker ${i}
After=docker.service
Requires=docker.service

[Service]
Type=simple
User=domaindesk
Group=domaindesk
Restart=always
RestartSec=10
WorkingDirectory=/home/domaindesk/DomainDesk

ExecStart=/usr/bin/docker compose run --rm --name queue-worker-${i} \
  app php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF

  sudo systemctl enable domaindesk-queue-${i}
  sudo systemctl start domaindesk-queue-${i}
done
```

## Cron Job Configuration

### Laravel Scheduler

Add to crontab for domaindesk user:

```bash
# Edit crontab
sudo su - domaindesk
crontab -e
```

Add this line:

```cron
* * * * * cd /home/domaindesk/DomainDesk && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

Or use the scheduler container (already configured in docker-compose.prod.yml).

### Backup Cron Job

```cron
# Daily database backup at 2 AM
0 2 * * * cd /home/domaindesk/DomainDesk && docker compose -f docker-compose.prod.yml --profile backup run --rm backup

# Weekly full backup at 3 AM on Sundays
0 3 * * 0 /home/domaindesk/DomainDesk/scripts/full-backup.sh

# Clean old backups (keep last 30 days)
0 4 * * * find /home/domaindesk/DomainDesk/backups -name "*.sql.gz" -mtime +30 -delete
```

## Monitoring Setup

### Basic Health Checks

Create `/home/domaindesk/DomainDesk/scripts/health-check.sh`:

```bash
#!/bin/bash

# Check if containers are running
CONTAINERS=("app" "postgres" "redis" "nginx" "queue")
for container in "${CONTAINERS[@]}"; do
    if ! docker compose ps | grep -q "$container.*running"; then
        echo "ERROR: Container $container is not running"
        # Send alert (email, Slack, etc.)
    fi
done

# Check application health endpoint
if ! curl -f -s http://localhost/health > /dev/null; then
    echo "ERROR: Application health check failed"
fi

# Check database connectivity
if ! docker compose exec -T postgres pg_isready > /dev/null; then
    echo "ERROR: Database is not ready"
fi

# Check Redis
if ! docker compose exec -T redis redis-cli ping > /dev/null; then
    echo "ERROR: Redis is not responding"
fi

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo "WARNING: Disk usage is at ${DISK_USAGE}%"
fi

# Check memory usage
MEM_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
if [ "$MEM_USAGE" -gt 85 ]; then
    echo "WARNING: Memory usage is at ${MEM_USAGE}%"
fi
```

Make executable and add to cron:

```bash
chmod +x /home/domaindesk/DomainDesk/scripts/health-check.sh

# Run every 5 minutes
*/5 * * * * /home/domaindesk/DomainDesk/scripts/health-check.sh
```

### Application Monitoring

```bash
# Install monitoring endpoints in routes/web.php
Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
});

Route::get('/health/detailed', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::has('health-check') ? 'working' : 'not working',
        'queue' => Redis::connection()->ping() ? 'connected' : 'disconnected',
    ]);
})->middleware('auth');
```

### Log Monitoring

```bash
# Monitor application logs
docker compose logs -f app | grep -i error

# Monitor queue failures
docker compose exec app php artisan queue:failed

# Monitor slow queries (if enabled)
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "SELECT query, mean_exec_time FROM pg_stat_statements \
      ORDER BY mean_exec_time DESC LIMIT 10;"
```

## Backup Strategy

### Automated Backup Script

Create `/home/domaindesk/DomainDesk/scripts/full-backup.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/home/domaindesk/backups"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/home/domaindesk/DomainDesk"

mkdir -p "${BACKUP_DIR}"

# Database backup
docker compose -f docker-compose.prod.yml exec -T postgres \
  pg_dump -U domaindesk -d domaindesk | \
  gzip > "${BACKUP_DIR}/db_${DATE}.sql.gz"

# Storage backup
tar czf "${BACKUP_DIR}/storage_${DATE}.tar.gz" \
  -C "${APP_DIR}" storage

# Environment backup
cp "${APP_DIR}/.env" "${BACKUP_DIR}/env_${DATE}.backup"

# Remove backups older than 30 days
find "${BACKUP_DIR}" -type f -mtime +30 -delete

echo "Backup completed: ${DATE}"
```

### Offsite Backup

```bash
# Upload to S3
aws s3 sync /home/domaindesk/backups s3://your-bucket/domaindesk-backups/

# Or use rsync to remote server
rsync -avz /home/domaindesk/backups/ \
  backup-server:/backups/domaindesk/
```

## Disaster Recovery

### Full System Recovery

```bash
# 1. Provision new server
# 2. Install Docker and dependencies
# 3. Clone repository
cd /home/domaindesk
git clone https://github.com/yourusername/DomainDesk.git
cd DomainDesk

# 4. Restore environment file
cp /path/to/env_backup .env

# 5. Restore database
gunzip -c /path/to/db_backup.sql.gz | \
  docker compose exec -T postgres psql -U domaindesk -d domaindesk

# 6. Restore storage
tar xzf /path/to/storage_backup.tar.gz -C ./

# 7. Start application
docker compose -f docker-compose.prod.yml up -d

# 8. Verify restoration
docker compose exec app php artisan migrate:status
curl https://yourdomain.com/health
```

### Database Point-in-Time Recovery

If using PostgreSQL WAL archiving:

```bash
# Configure in docker/postgres/postgresql.conf
wal_level = replica
archive_mode = on
archive_command = 'test ! -f /backups/archive/%f && cp %p /backups/archive/%f'
```

## Zero-Downtime Deployment

### Blue-Green Deployment Script

Create `/home/domaindesk/DomainDesk/scripts/deploy.sh`:

```bash
#!/bin/bash

set -e

APP_DIR="/home/domaindesk/DomainDesk"
cd "${APP_DIR}"

echo "Starting deployment..."

# Pull latest code
git fetch origin
git checkout main
git pull origin main

# Build new images
docker compose -f docker-compose.prod.yml build

# Run migrations (if any)
docker compose -f docker-compose.prod.yml run --rm app \
  php artisan migrate --force

# Scale up new containers
docker compose -f docker-compose.prod.yml up -d --scale app=4 --no-recreate

# Wait for new containers to be healthy
sleep 10

# Scale down old containers
docker compose -f docker-compose.prod.yml up -d --scale app=2

# Clear and optimize caches
docker compose -f docker-compose.prod.yml exec app php artisan optimize
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache

# Restart queue workers
sudo systemctl restart domaindesk-queue

echo "Deployment completed successfully!"
```

Make executable:

```bash
chmod +x /home/domaindesk/DomainDesk/scripts/deploy.sh
```

### Using GitHub Actions

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: domaindesk
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /home/domaindesk/DomainDesk
            ./scripts/deploy.sh
```

## Rollback Procedures

### Quick Rollback

```bash
# List recent commits
git log --oneline -n 10

# Rollback to specific commit
git reset --hard <commit-hash>

# Rebuild and restart
docker compose -f docker-compose.prod.yml up -d --build

# Rollback migrations if needed
docker compose exec app php artisan migrate:rollback --step=1

# Clear caches
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
```

### Automated Rollback Script

Create `/home/domaindesk/DomainDesk/scripts/rollback.sh`:

```bash
#!/bin/bash

set -e

APP_DIR="/home/domaindesk/DomainDesk"
cd "${APP_DIR}"

# Get previous commit
PREVIOUS_COMMIT=$(git rev-parse HEAD~1)

echo "Rolling back to commit: ${PREVIOUS_COMMIT}"

# Checkout previous commit
git reset --hard "${PREVIOUS_COMMIT}"

# Rebuild containers
docker compose -f docker-compose.prod.yml up -d --build

# Rollback database (if needed)
read -p "Rollback database? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker compose exec app php artisan migrate:rollback
fi

# Restart services
sudo systemctl restart domaindesk-queue

echo "Rollback completed!"
```

### Database Rollback

```bash
# View migration status
docker compose exec app php artisan migrate:status

# Rollback last batch
docker compose exec app php artisan migrate:rollback

# Rollback specific steps
docker compose exec app php artisan migrate:rollback --step=3

# Restore from backup
gunzip -c backups/db_20240101_120000.sql.gz | \
  docker compose exec -T postgres psql -U domaindesk -d domaindesk
```

## Maintenance Mode

```bash
# Enable maintenance mode
docker compose exec app php artisan down --secret="maintenance-bypass-token"

# Perform maintenance
./scripts/deploy.sh

# Disable maintenance mode
docker compose exec app php artisan up

# Access during maintenance
https://yourdomain.com?secret=maintenance-bypass-token
```

## Performance Tuning

```bash
# Optimize autoloader
docker compose exec app composer dump-autoload --optimize --classmap-authoritative

# Enable OPcache
# Already configured in docker/php/opcache.ini

# Configure queue workers for high load
docker compose -f docker-compose.prod.yml up -d --scale queue=10

# Database optimization
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "VACUUM ANALYZE;"
```

## Additional Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Docker Production Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [PostgreSQL Backup and Recovery](https://www.postgresql.org/docs/current/backup.html)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
