# Operations Guide for DomainDesk

This guide covers daily operations, monitoring, maintenance, and troubleshooting for DomainDesk.

## Table of Contents

- [Monitoring Checklist](#monitoring-checklist)
- [Performance Optimization](#performance-optimization)
- [Scaling Guidelines](#scaling-guidelines)
- [Database Maintenance](#database-maintenance)
- [Log Management](#log-management)
- [Security Updates](#security-updates)
- [Health Checks](#health-checks)
- [Troubleshooting](#troubleshooting)
- [Incident Response](#incident-response)

## Monitoring Checklist

### Daily Checks

```bash
# Application status
docker compose ps

# Check health endpoint
curl -s http://localhost/health | jq

# Review error logs (last 24 hours)
docker compose logs --since 24h app | grep -i error

# Check queue status
docker compose exec app php artisan queue:monitor redis:default

# Failed jobs
docker compose exec app php artisan queue:failed

# Database connections
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "SELECT count(*) FROM pg_stat_activity;"

# Disk usage
df -h /

# Memory usage
free -h
```

### Weekly Checks

```bash
# Review backup status
ls -lh backups/

# Check for security updates
sudo apt update
apt list --upgradable

# Database size and growth
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "SELECT pg_size_pretty(pg_database_size('domaindesk'));"

# Review slow queries
docker compose exec app php artisan telescope:prune --hours=168

# Check SSL certificate expiry
echo | openssl s_client -servername yourdomain.com \
  -connect yourdomain.com:443 2>/dev/null | \
  openssl x509 -noout -dates

# Review application logs for patterns
docker compose logs --since 7d app | \
  grep -E "(error|warning|critical)" | \
  awk '{print $1}' | sort | uniq -c | sort -rn
```

### Monthly Checks

```bash
# Review database performance
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "SELECT schemaname, tablename, pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size 
      FROM pg_tables 
      WHERE schemaname NOT IN ('pg_catalog', 'information_schema') 
      ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC 
      LIMIT 10;"

# Analyze query performance
docker compose exec app php artisan tinker
>>> DB::table('telescope_entries')->where('type', 'query')->count()

# Review storage usage
du -sh storage/*

# Check for abandoned sessions
docker compose exec app php artisan session:gc

# Review user activity
docker compose exec app php artisan tinker
>>> User::where('last_login_at', '<', now()->subMonths(3))->count()
```

## Performance Optimization

### Application Optimization

```bash
# Clear all caches
docker compose exec app php artisan optimize:clear

# Optimize for production
docker compose exec app php artisan optimize
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan event:cache

# Optimize composer autoloader
docker compose exec app composer dump-autoload --optimize --classmap-authoritative
```

### Database Optimization

```bash
# Analyze and vacuum database
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "VACUUM ANALYZE;"

# Reindex tables
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "REINDEX DATABASE domaindesk;"

# Update statistics
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "ANALYZE;"

# Check for missing indexes
docker compose exec postgres psql -U domaindesk -d domaindesk << 'EOF'
SELECT schemaname, tablename, attname, n_distinct, correlation
FROM pg_stats
WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
  AND n_distinct > 100
  AND correlation < 0.5
ORDER BY n_distinct DESC
LIMIT 20;
EOF

# Identify slow queries
docker compose exec postgres psql -U domaindesk -d domaindesk << 'EOF'
SELECT 
    query,
    calls,
    total_exec_time,
    mean_exec_time,
    max_exec_time
FROM pg_stat_statements
ORDER BY mean_exec_time DESC
LIMIT 10;
EOF
```

### Redis Optimization

```bash
# Check Redis memory usage
docker compose exec redis redis-cli info memory

# Check key count
docker compose exec redis redis-cli dbsize

# Analyze slow queries
docker compose exec redis redis-cli slowlog get 10

# Configure maxmemory policy
docker compose exec redis redis-cli config set maxmemory-policy allkeys-lru

# Flush expired keys
docker compose exec redis redis-cli --scan --pattern "laravel_cache:*" | \
  xargs docker compose exec redis redis-cli del
```

### PHP-FPM Tuning

Edit `docker/php/php.ini`:

```ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M

opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

Restart containers:

```bash
docker compose restart app
```

### Queue Optimization

```bash
# Monitor queue size
docker compose exec app php artisan queue:monitor redis:default,redis:high

# Process specific queues with priority
docker compose exec app php artisan queue:work \
  --queue=high,default,low \
  --tries=3 \
  --timeout=300 \
  --memory=512

# Scale queue workers
docker compose up -d --scale queue=5

# Process jobs in batches
docker compose exec app php artisan queue:work --max-jobs=1000
```

## Scaling Guidelines

### Vertical Scaling (Single Server)

#### Increase Container Resources

Edit `docker-compose.prod.yml`:

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '4.0'
          memory: 2G
        reservations:
          cpus: '2.0'
          memory: 1G
```

Apply changes:

```bash
docker compose -f docker-compose.prod.yml up -d --force-recreate
```

#### Optimize PostgreSQL

Edit `docker/postgres/postgresql.conf`:

```ini
# Memory settings
shared_buffers = 2GB
effective_cache_size = 6GB
maintenance_work_mem = 512MB
work_mem = 32MB

# Checkpoint settings
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100

# Parallel query settings
max_worker_processes = 4
max_parallel_workers_per_gather = 2
max_parallel_workers = 4
```

### Horizontal Scaling (Multiple Servers)

#### Load Balancer Setup

Using HAProxy:

```bash
# Install HAProxy
sudo apt install -y haproxy

# Configure /etc/haproxy/haproxy.cfg
```

```haproxy
global
    log /dev/log local0
    maxconn 4096
    
defaults
    mode http
    timeout connect 5000ms
    timeout client 50000ms
    timeout server 50000ms
    
frontend http-in
    bind *:80
    bind *:443 ssl crt /etc/ssl/certs/yourdomain.pem
    redirect scheme https if !{ ssl_fc }
    default_backend app-servers
    
backend app-servers
    balance roundrobin
    option httpchk GET /health
    http-check expect status 200
    server app1 192.168.1.10:8000 check
    server app2 192.168.1.11:8000 check
    server app3 192.168.1.12:8000 check
```

#### Database Replication

Primary server:

```bash
# Configure postgresql.conf
wal_level = replica
max_wal_senders = 3
wal_keep_size = 1GB
```

Replica server:

```bash
# Stop PostgreSQL
docker compose stop postgres

# Remove old data
rm -rf postgres_data/*

# Create base backup from primary
pg_basebackup -h primary-server -D postgres_data -U replication -P -v -R

# Start replica
docker compose up -d postgres
```

#### Session Management

Use Redis for session storage across multiple servers:

```bash
# .env
SESSION_DRIVER=redis
SESSION_CONNECTION=default
```

### Auto-Scaling with Docker Swarm

```bash
# Initialize swarm
docker swarm init

# Deploy stack
docker stack deploy -c docker-compose.prod.yml domaindesk

# Scale services
docker service scale domaindesk_app=5
docker service scale domaindesk_queue=10

# Monitor services
docker service ls
docker service ps domaindesk_app
```

## Database Maintenance

### Regular Maintenance Tasks

```bash
# Daily vacuum (automatic)
# Configure in postgresql.conf:
autovacuum = on
autovacuum_max_workers = 3

# Weekly full vacuum (manual)
docker compose exec postgres vacuumdb -U domaindesk -d domaindesk --full --analyze

# Monthly reindex
docker compose exec postgres reindexdb -U domaindesk -d domaindesk
```

### Performance Analysis

```bash
# Check table bloat
docker compose exec postgres psql -U domaindesk -d domaindesk << 'EOF'
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename) - pg_relation_size(schemaname||'.'||tablename)) as external_size
FROM pg_tables
WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 10;
EOF

# Check index usage
docker compose exec postgres psql -U domaindesk -d domaindesk << 'EOF'
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
ORDER BY idx_scan ASC
LIMIT 20;
EOF

# Find unused indexes
docker compose exec postgres psql -U domaindesk -d domaindesk << 'EOF'
SELECT 
    schemaname,
    tablename,
    indexname,
    pg_size_pretty(pg_relation_size(indexrelid)) as index_size
FROM pg_stat_user_indexes
WHERE idx_scan = 0
  AND indexrelname NOT LIKE '%_pkey'
ORDER BY pg_relation_size(indexrelid) DESC;
EOF
```

### Data Archiving

```bash
# Archive old data (example: old logs)
docker compose exec app php artisan tinker
>>> DB::table('activity_log')->where('created_at', '<', now()->subMonths(6))->delete()

# Export to archive table
docker compose exec postgres psql -U domaindesk -d domaindesk << 'EOF'
CREATE TABLE activity_log_archive AS
SELECT * FROM activity_log WHERE created_at < NOW() - INTERVAL '6 months';

DELETE FROM activity_log WHERE created_at < NOW() - INTERVAL '6 months';
EOF
```

## Log Management

### Log Rotation

Create `/etc/logrotate.d/domaindesk`:

```
/var/log/domaindesk/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 domaindesk domaindesk
    sharedscripts
    postrotate
        docker compose -f /home/domaindesk/DomainDesk/docker-compose.prod.yml restart app
    endscript
}
```

### Log Analysis

```bash
# Monitor real-time logs
docker compose logs -f --tail=100 app

# Search for errors
docker compose logs --since 24h app | grep -i "error\|exception\|fatal"

# Count error types
docker compose logs --since 7d app | \
  grep -oP "(?<=exception: ')[^']*" | \
  sort | uniq -c | sort -rn

# Analyze response times
docker compose logs app | \
  grep "Processing:" | \
  awk '{print $NF}' | \
  awk '{sum+=$1; count++} END {print "Average:", sum/count, "ms"}'

# Export logs for analysis
docker compose logs --since 24h app > /tmp/app-logs-$(date +%Y%m%d).log
```

### Centralized Logging

Using ELK Stack:

```yaml
# docker-compose.logging.yml
services:
  elasticsearch:
    image: elasticsearch:8.10.0
    environment:
      - discovery.type=single-node
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data
      
  logstash:
    image: logstash:8.10.0
    volumes:
      - ./logstash/pipeline:/usr/share/logstash/pipeline
      
  kibana:
    image: kibana:8.10.0
    ports:
      - "5601:5601"
    depends_on:
      - elasticsearch
```

Configure Logstash pipeline:

```ruby
# logstash/pipeline/logstash.conf
input {
  gelf {
    port => 12201
  }
}

filter {
  json {
    source => "message"
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "domaindesk-logs-%{+YYYY.MM.dd}"
  }
}
```

## Security Updates

### System Updates

```bash
# Check for updates
sudo apt update
apt list --upgradable

# Apply security updates only
sudo apt install unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades

# Manual security updates
sudo apt update
sudo apt upgrade -y

# Reboot if required
if [ -f /var/run/reboot-required ]; then
    echo "Reboot required"
    sudo reboot
fi
```

### Application Updates

```bash
# Update dependencies
docker compose exec app composer update

# Check for vulnerabilities
docker compose exec app composer audit

# Update Node dependencies
docker compose exec app npm audit
docker compose exec app npm audit fix

# Update Docker base images
docker compose pull
docker compose up -d --build
```

### Security Scanning

```bash
# Scan Docker images
docker scan domaindesk:latest

# Check for CVEs
docker compose exec app composer require --dev roave/security-advisories:dev-latest

# Scan dependencies
docker run --rm -v $(pwd):/app local/php-security-checker /app/composer.lock
```

## Health Checks

### Application Health Check

Create `routes/web.php` endpoint:

```php
Route::get('/health', function () {
    $checks = [
        'app' => true,
        'database' => false,
        'cache' => false,
        'queue' => false,
        'storage' => false,
    ];
    
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        Log::error('Database health check failed: ' . $e->getMessage());
    }
    
    try {
        Cache::put('health-check', true, 10);
        $checks['cache'] = Cache::get('health-check') === true;
    } catch (\Exception $e) {
        Log::error('Cache health check failed: ' . $e->getMessage());
    }
    
    try {
        $checks['queue'] = Redis::connection()->ping();
    } catch (\Exception $e) {
        Log::error('Queue health check failed: ' . $e->getMessage());
    }
    
    try {
        $checks['storage'] = Storage::exists('.gitignore');
    } catch (\Exception $e) {
        Log::error('Storage health check failed: ' . $e->getMessage());
    }
    
    $healthy = !in_array(false, $checks, true);
    
    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});
```

### Automated Monitoring Script

Create `/home/domaindesk/DomainDesk/scripts/monitor.sh`:

```bash
#!/bin/bash

LOG_FILE="/var/log/domaindesk/monitor.log"
ALERT_EMAIL="admin@yourdomain.com"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

alert() {
    log "ALERT: $1"
    echo "$1" | mail -s "DomainDesk Alert" "$ALERT_EMAIL"
}

# Check application health
if ! curl -sf http://localhost/health > /dev/null; then
    alert "Application health check failed"
fi

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 85 ]; then
    alert "Disk usage critical: ${DISK_USAGE}%"
fi

# Check memory
MEM_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
if [ "$MEM_USAGE" -gt 90 ]; then
    alert "Memory usage critical: ${MEM_USAGE}%"
fi

# Check failed jobs
FAILED_JOBS=$(docker compose exec -T app php artisan queue:failed | wc -l)
if [ "$FAILED_JOBS" -gt 10 ]; then
    alert "Too many failed jobs: ${FAILED_JOBS}"
fi

# Check database connections
DB_CONNECTIONS=$(docker compose exec -T postgres psql -U domaindesk -d domaindesk -t \
  -c "SELECT count(*) FROM pg_stat_activity;")
if [ "$DB_CONNECTIONS" -gt 100 ]; then
    alert "High database connections: ${DB_CONNECTIONS}"
fi

log "Health check completed"
```

## Troubleshooting

### Application Not Responding

```bash
# Check if containers are running
docker compose ps

# Check application logs
docker compose logs -f app

# Check resource usage
docker stats

# Restart application
docker compose restart app

# If still not working, rebuild
docker compose up -d --build --force-recreate
```

### High CPU Usage

```bash
# Identify the process
docker stats --no-stream

# Check for slow queries
docker compose exec postgres psql -U domaindesk -d domaindesk << 'EOF'
SELECT pid, usename, query, state, query_start
FROM pg_stat_activity
WHERE state != 'idle'
ORDER BY query_start;
EOF

# Check queue processing
docker compose exec app php artisan queue:monitor

# Check for infinite loops in code
docker compose exec app php artisan tinker
```

### High Memory Usage

```bash
# Check container memory
docker stats --no-stream

# Check for memory leaks
docker compose exec app php -i | grep memory_limit

# Clear caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear

# Restart PHP-FPM
docker compose restart app
```

### Database Connection Issues

```bash
# Check PostgreSQL status
docker compose exec postgres pg_isready

# Check connection pool
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "SELECT * FROM pg_stat_activity;"

# Kill idle connections
docker compose exec postgres psql -U domaindesk -d domaindesk \
  -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity 
      WHERE state = 'idle' AND state_change < NOW() - INTERVAL '5 minutes';"

# Restart PostgreSQL
docker compose restart postgres
```

### Queue Not Processing

```bash
# Check queue worker status
sudo systemctl status domaindesk-queue

# View queue worker logs
docker compose logs -f queue

# Check Redis connection
docker compose exec redis redis-cli ping

# Manually process queue
docker compose exec app php artisan queue:work --once

# Restart queue workers
sudo systemctl restart domaindesk-queue
```

### Slow Performance

```bash
# Enable query logging
# In .env: LOG_LEVEL=debug

# Check slow queries
docker compose logs app | grep "slow query"

# Profile with Telescope
docker compose exec app php artisan telescope:install
docker compose exec app php artisan migrate

# Check OPcache status
docker compose exec app php -i | grep opcache

# Clear all caches
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
```

## Incident Response

### Incident Response Plan

1. **Detection**
   - Monitor alerts from health checks
   - User reports
   - Automated monitoring tools

2. **Assessment**
   ```bash
   # Quick assessment
   docker compose ps
   curl http://localhost/health
   docker compose logs --tail=100 app
   ```

3. **Containment**
   ```bash
   # Enable maintenance mode
   docker compose exec app php artisan down
   
   # Scale down if needed
   docker compose stop queue
   ```

4. **Resolution**
   ```bash
   # Apply fix
   git pull origin main
   docker compose up -d --build
   
   # Verify fix
   curl http://localhost/health
   docker compose logs -f app
   ```

5. **Recovery**
   ```bash
   # Disable maintenance mode
   docker compose exec app php artisan up
   
   # Resume normal operations
   docker compose start queue
   ```

6. **Post-Incident**
   - Document incident
   - Analyze root cause
   - Implement preventive measures
   - Update runbooks

### Emergency Contacts

Create `/home/domaindesk/DomainDesk/docs/CONTACTS.md`:

```markdown
# Emergency Contacts

## On-Call Engineers
- Primary: John Doe - +1-555-0001
- Secondary: Jane Smith - +1-555-0002

## Service Providers
- Hosting: support@provider.com
- DNS: support@dns-provider.com
- Email: support@email-provider.com

## Escalation Path
1. On-call engineer (15 min response)
2. Team lead (30 min response)
3. CTO (1 hour response)
```

### Incident Log

Maintain an incident log at `/var/log/domaindesk/incidents.log`:

```
[2024-01-15 14:30] CRITICAL - Database connection pool exhausted
  Impact: Service unavailable for 15 minutes
  Resolution: Restarted PostgreSQL, increased max_connections
  Root Cause: Traffic spike from marketing campaign
  
[2024-01-10 09:15] WARNING - Disk space at 90%
  Impact: No service impact
  Resolution: Cleaned old logs and backups
  Prevention: Implemented automated cleanup
```

## Additional Resources

- [Laravel Performance Optimization](https://laravel.com/docs/deployment#optimization)
- [PostgreSQL Performance Tuning](https://wiki.postgresql.org/wiki/Performance_Optimization)
- [Docker Container Monitoring](https://docs.docker.com/config/containers/runmetrics/)
- [Redis Best Practices](https://redis.io/topics/admin)
