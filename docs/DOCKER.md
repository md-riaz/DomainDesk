# Docker Guide for DomainDesk

This guide covers Docker setup, configuration, and best practices for DomainDesk.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Building Docker Images](#building-docker-images)
- [Running Containers](#running-containers)
- [Docker Compose Usage](#docker-compose-usage)
- [Scaling Services](#scaling-services)
- [Backup Procedures](#backup-procedures)
- [Troubleshooting](#troubleshooting)
- [Best Practices](#best-practices)

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- At least 4GB RAM available for containers
- 20GB disk space for images and volumes

```bash
# Verify Docker installation
docker --version
docker compose version

# Check Docker daemon status
systemctl status docker
```

## Building Docker Images

### Development Build

```bash
# Build all services
docker compose build

# Build specific service
docker compose build app

# Build with no cache (fresh build)
docker compose build --no-cache

# Build with progress output
docker compose build --progress=plain
```

### Production Build

```bash
# Build production image with optimizations
docker compose -f docker-compose.prod.yml build

# Build with build arguments
docker compose -f docker-compose.prod.yml build \
  --build-arg BUILD_ENV=production \
  --build-arg PHP_VERSION=8.2

# Multi-platform build (for ARM/AMD64)
docker buildx build \
  --platform linux/amd64,linux/arm64 \
  -t domaindesk:latest \
  -f Dockerfile .
```

### Image Optimization

```bash
# Check image size
docker images domaindesk

# Inspect image layers
docker history domaindesk:latest

# Remove dangling images
docker image prune

# Remove all unused images
docker image prune -a
```

## Running Containers

### Development Environment

```bash
# Start all services
docker compose up

# Start in detached mode
docker compose up -d

# Start specific services
docker compose up app postgres redis

# View logs
docker compose logs -f

# View logs for specific service
docker compose logs -f app

# Follow last 100 lines
docker compose logs -f --tail=100 app
```

### Production Environment

```bash
# Start production stack
docker compose -f docker-compose.prod.yml up -d

# Verify all services are running
docker compose -f docker-compose.prod.yml ps

# Check health status
docker compose -f docker-compose.prod.yml ps --filter "health=healthy"
```

### Container Management

```bash
# Stop all containers
docker compose down

# Stop and remove volumes
docker compose down -v

# Restart specific service
docker compose restart app

# Execute commands in running container
docker compose exec app php artisan migrate

# Open shell in container
docker compose exec app sh

# Run one-off command
docker compose run --rm app php artisan tinker
```

## Docker Compose Usage

### Environment Configuration

Create `.env` file with required variables:

```bash
# Application
APP_NAME=DomainDesk
APP_ENV=production
APP_KEY=base64:your-32-char-key-here
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=pgsql
DB_DATABASE=domaindesk
DB_USERNAME=domaindesk_user
DB_PASSWORD=secure_password_here

# Redis
REDIS_PASSWORD=secure_redis_password

# Ports
APP_PORT=8000
NGINX_PORT=80
NGINX_SSL_PORT=443
```

### Service Configuration

#### Application Service

```yaml
# Custom app configuration
services:
  app:
    environment:
      - PHP_MEMORY_LIMIT=512M
      - PHP_MAX_EXECUTION_TIME=300
      - PHP_UPLOAD_MAX_FILESIZE=64M
```

#### Queue Worker Configuration

```bash
# Scale queue workers
docker compose up -d --scale queue=5

# Configure worker parameters
docker compose exec queue php artisan queue:work \
  --sleep=3 \
  --tries=3 \
  --max-time=3600 \
  --max-jobs=1000 \
  --memory=256
```

#### Scheduler Configuration

The scheduler runs Laravel's task scheduler every minute:

```bash
# View scheduler output
docker compose logs -f scheduler

# Run scheduler manually
docker compose exec scheduler php artisan schedule:run
```

### Database Operations

```bash
# Run migrations
docker compose exec app php artisan migrate

# Fresh migration with seeders
docker compose exec app php artisan migrate:fresh --seed

# Rollback migrations
docker compose exec app php artisan migrate:rollback

# Database backup
docker compose exec postgres pg_dump \
  -U domaindesk -d domaindesk > backup.sql

# Restore database
docker compose exec -T postgres psql \
  -U domaindesk -d domaindesk < backup.sql
```

### Cache Operations

```bash
# Clear all caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Optimize for production
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan optimize
```

## Scaling Services

### Horizontal Scaling

#### Application Scaling

```bash
# Scale app service to 3 instances
docker compose up -d --scale app=3

# With load balancer (nginx)
docker compose -f docker-compose.prod.yml up -d --scale app=3
```

#### Queue Worker Scaling

```bash
# Scale workers based on load
docker compose up -d --scale queue=5

# Different queues with different scaling
docker compose run -d --name queue-high \
  app php artisan queue:work --queue=high --tries=3

docker compose run -d --name queue-default \
  app php artisan queue:work --queue=default --tries=3
```

### Vertical Scaling

Update `docker-compose.prod.yml` resource limits:

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '2.0'      # 2 CPU cores
          memory: 1G       # 1GB RAM
        reservations:
          cpus: '1.0'
          memory: 512M
```

Apply changes:

```bash
docker compose -f docker-compose.prod.yml up -d --force-recreate
```

### Load Balancing

Configure Nginx upstream in `docker/nginx/nginx-prod.conf`:

```nginx
upstream app_servers {
    least_conn;
    server app:80 max_fails=3 fail_timeout=30s;
    server app_2:80 max_fails=3 fail_timeout=30s;
    server app_3:80 max_fails=3 fail_timeout=30s;
}
```

## Backup Procedures

### Database Backups

#### Automated Backup Script

Create `docker/backup/backup.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
FILENAME="domaindesk_backup_${DATE}.sql.gz"

pg_dump -U $POSTGRES_USER -d $POSTGRES_DB | gzip > "${BACKUP_DIR}/${FILENAME}"

# Keep only last 7 days of backups
find ${BACKUP_DIR} -name "*.sql.gz" -mtime +7 -delete

echo "Backup completed: ${FILENAME}"
```

Run backup:

```bash
# Manual backup
docker compose -f docker-compose.prod.yml \
  --profile backup run --rm backup

# Schedule with cron
0 2 * * * docker compose -f /path/to/docker-compose.prod.yml \
  --profile backup run --rm backup
```

#### Volume Backups

```bash
# Backup PostgreSQL volume
docker run --rm \
  -v domaindesk_postgres_data:/data \
  -v $(pwd)/backups:/backup \
  alpine tar czf /backup/postgres_$(date +%Y%m%d).tar.gz -C /data .

# Backup Redis volume
docker run --rm \
  -v domaindesk_redis_data:/data \
  -v $(pwd)/backups:/backup \
  alpine tar czf /backup/redis_$(date +%Y%m%d).tar.gz -C /data .

# Backup storage volume
docker run --rm \
  -v domaindesk_storage:/data \
  -v $(pwd)/backups:/backup \
  alpine tar czf /backup/storage_$(date +%Y%m%d).tar.gz -C /data .
```

### Restore Procedures

```bash
# Restore database from SQL dump
gunzip -c backups/domaindesk_backup_20240101.sql.gz | \
  docker compose exec -T postgres psql -U domaindesk -d domaindesk

# Restore PostgreSQL volume
docker compose down postgres
docker run --rm \
  -v domaindesk_postgres_data:/data \
  -v $(pwd)/backups:/backup \
  alpine tar xzf /backup/postgres_20240101.tar.gz -C /data
docker compose up -d postgres

# Restore storage volume
docker run --rm \
  -v domaindesk_storage:/data \
  -v $(pwd)/backups:/backup \
  alpine tar xzf /backup/storage_20240101.tar.gz -C /data
```

## Troubleshooting

### Common Issues

#### Container Won't Start

```bash
# Check container status
docker compose ps

# View container logs
docker compose logs app

# Inspect container
docker inspect domaindesk-app

# Check resource usage
docker stats

# Verify health checks
docker compose ps --filter "health=unhealthy"
```

#### Database Connection Issues

```bash
# Verify postgres is running
docker compose ps postgres

# Check postgres logs
docker compose logs postgres

# Test connection from app container
docker compose exec app psql -h postgres -U domaindesk -d domaindesk

# Check network connectivity
docker compose exec app ping postgres
docker compose exec app nc -zv postgres 5432
```

#### Permission Issues

```bash
# Fix storage permissions
docker compose exec app chown -R www-data:www-data /var/www/html/storage
docker compose exec app chmod -R 775 /var/www/html/storage

# Fix cache permissions
docker compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/html/bootstrap/cache
```

#### Memory Issues

```bash
# Check container memory usage
docker stats --no-stream

# Increase memory limit
# Edit docker-compose.yml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 1G
```

#### Disk Space Issues

```bash
# Check disk usage
docker system df

# Clean up unused resources
docker system prune

# Remove stopped containers
docker container prune

# Remove unused volumes
docker volume prune

# Remove unused images
docker image prune -a
```

### Performance Issues

```bash
# Monitor container resources
docker stats

# Check container logs for slow queries
docker compose logs app | grep "Slow query"

# Profile PHP performance
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache

# Check database performance
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "SELECT * FROM pg_stat_activity;"
```

### Network Issues

```bash
# List networks
docker network ls

# Inspect network
docker network inspect domaindesk_domaindesk

# Test connectivity between containers
docker compose exec app ping postgres
docker compose exec app nc -zv redis 6379

# Recreate network
docker compose down
docker network rm domaindesk_domaindesk
docker compose up -d
```

## Best Practices

### Image Management

1. **Use Multi-Stage Builds**: Reduces final image size
2. **Pin Base Image Versions**: Use specific tags (e.g., `php:8.2-fpm-alpine`)
3. **Minimize Layers**: Combine RUN commands where possible
4. **Use .dockerignore**: Exclude unnecessary files

```dockerfile
# .dockerignore
.git
.env
node_modules
vendor
tests
*.md
```

### Security

1. **Don't Run as Root**:
```dockerfile
USER www-data
```

2. **Use Secrets for Sensitive Data**:
```yaml
secrets:
  db_password:
    file: ./secrets/db_password.txt
```

3. **Scan Images for Vulnerabilities**:
```bash
docker scan domaindesk:latest
```

4. **Keep Base Images Updated**:
```bash
docker compose pull
docker compose up -d --build
```

### Resource Management

1. **Set Resource Limits**:
```yaml
deploy:
  resources:
    limits:
      cpus: '1.0'
      memory: 512M
```

2. **Configure Health Checks**:
```yaml
healthcheck:
  test: ["CMD", "curl", "-f", "http://localhost/health"]
  interval: 30s
  timeout: 10s
  retries: 3
```

3. **Use Appropriate Restart Policies**:
```yaml
restart: unless-stopped  # Development
restart: always          # Production
```

### Logging

1. **Configure Log Drivers**:
```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

2. **Centralized Logging**:
```bash
# Send logs to external system
docker compose logs -f | tee /var/log/domaindesk/app.log
```

### Monitoring

```bash
# Export container metrics
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"

# Health check endpoint
curl http://localhost/health

# Monitor queue workers
docker compose exec app php artisan queue:monitor redis:default
```

### Development Workflow

1. **Use Volume Mounts for Hot Reload**:
```yaml
volumes:
  - ./app:/var/www/html/app
  - ./resources:/var/www/html/resources
```

2. **Separate Dev and Prod Configs**:
```bash
# Development
docker compose up

# Production
docker compose -f docker-compose.prod.yml up
```

3. **Use Docker Compose Override**:
```yaml
# docker-compose.override.yml
services:
  app:
    environment:
      - APP_DEBUG=true
      - XDEBUG_MODE=debug
```

### Maintenance

```bash
# Regular cleanup (weekly)
docker system prune -f

# Update images (monthly)
docker compose pull
docker compose up -d --build

# Backup volumes (daily)
./scripts/backup.sh

# Monitor disk usage
docker system df
```

### CI/CD Integration

```yaml
# .github/workflows/docker.yml
name: Docker Build
on: [push]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Build image
        run: docker compose build
      - name: Run tests
        run: docker compose run --rm app php artisan test
```

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Docker Best Practices](https://laravel.com/docs/deployment#optimization)
- [PostgreSQL Docker Guide](https://hub.docker.com/_/postgres)
