# IHC Catalog Service - Deployment Manual

## Overview

The IHC Catalog Service is a Laravel-based microservice for managing international health care products with multi-language support, JWT authentication, and comprehensive API endpoints.

## System Requirements

### Minimum Requirements
- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Web Server**: Apache 2.4+ or Nginx 1.20+
- **Composer**: 2.0+
- **Node.js**: 18.0+ (for asset compilation)
- **Memory**: 512MB RAM minimum, 1GB recommended
- **Storage**: 1GB free space

### Recommended Production Setup
- **PHP**: 8.3+
- **Database**: MySQL 8.0+ with InnoDB
- **Web Server**: Nginx with PHP-FPM
- **Cache**: Redis 6.0+
- **SSL**: Let's Encrypt or commercial SSL certificate
- **CDN**: CloudFlare or AWS CloudFront for static assets

## Environment Configuration

### 1. Environment Variables

Create a production `.env` file with the following variables:

```bash
# Application
APP_NAME="IHC Catalog Service"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ihc_catalog_prod
DB_USERNAME=ihc_user
DB_PASSWORD=YOUR_SECURE_DB_PASSWORD

# JWT Authentication
JWT_SECRET=f10d08554d83513cc75911ad1899030f0958620414fc971f2b5ae7dd5c424e639165abc63bd7345c5f076ca3d5d09d0ab23369833c6af0c037d4ee636f0a05b4

# Cache & Sessions
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis (if using Redis)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue (optional, for background processing)
QUEUE_CONNECTION=database

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error

# Mail (optional)
MAIL_MAILER=log
```

### 2. Generate Application Key

```bash
php artisan key:generate
```

### 3. JWT Secret Key

The JWT secret key is pre-configured in the environment. **Never change this key in production** as it will invalidate all existing JWT tokens.

## Database Setup

### 1. Create Database

```sql
CREATE DATABASE ihc_catalog_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ihc_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON ihc_catalog_prod.* TO 'ihc_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Run Migrations

```bash
php artisan migrate --force
```

### 3. Seed Essential Data

```bash
php artisan db:seed --force
```

### 4. Alternative: Fresh Database Setup

```bash
php artisan ihc:setup-fresh-database --seed
```

## Security Configuration

### 1. File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/ihc-catalog
sudo chown -R www-data:www-data /var/www/ihc-catalog/storage
sudo chown -R www-data:www-data /var/www/ihc-catalog/bootstrap/cache

# Set proper permissions
sudo find /var/www/ihc-catalog -type f -exec chmod 644 {} \;
sudo find /var/www/ihc-catalog -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/ihc-catalog/storage
sudo chmod -R 775 /var/www/ihc-catalog/bootstrap/cache
```

### 2. SSL Configuration

Ensure SSL is properly configured. The application requires HTTPS in production.

### 3. Security Headers

The application automatically adds security headers:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Content-Security-Policy: default-src 'self'`

## Deployment Steps

### 1. Code Deployment

```bash
# Clone repository
git clone https://github.com/your-org/ihc-catalog.git
cd ihc-catalog

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies and build assets
npm ci
npm run build

# Set environment file
cp .env.example .env
# Edit .env with production values
```

### 2. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed data
php artisan db:seed --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Storage Setup

```bash
# Create storage link
php artisan storage:link

# Set proper permissions for uploads
sudo chown -R www-data:www-data storage/app/public
sudo chmod -R 775 storage/app/public
```

### 4. Web Server Configuration

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    # SSL Configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;

    root /var/www/ihc-catalog/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ \.(env|git) {
        deny all;
    }
}
```

#### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/ihc-catalog/public

    SSLEngine on
    SSLCertificateFile /path/to/ssl/cert.pem
    SSLCertificateKeyFile /path/to/ssl/private.key

    <Directory /var/www/ihc-catalog/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Security headers
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"

    # Deny access to sensitive files
    <FilesMatch "\.(env|git)">
        Require all denied
    </FilesMatch>
</VirtualHost>
```

### 5. Process Management

#### Using Supervisor (Recommended)

Create `/etc/supervisor/conf.d/ihc-catalog.conf`:

```ini
[program:ihc-catalog-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ihc-catalog/artisan queue:work --sleep=3 --tries=3 --max-jobs=1000
directory=/var/www/ihc-catalog
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ihc-catalog/storage/logs/queue.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ihc-catalog-queue:*
```

#### Using systemd

Create `/etc/systemd/system/ihc-catalog-queue.service`:

```ini
[Unit]
Description=IHC Catalog Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/ihc-catalog
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-jobs=1000
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable ihc-catalog-queue
sudo systemctl start ihc-catalog-queue
```

## Monitoring & Maintenance

### 1. Health Checks

The application includes a health check endpoint:

```bash
curl https://your-domain.com/up
```

### 2. Log Monitoring

```bash
# View recent logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Monitor error logs
grep "ERROR" storage/logs/laravel-*.log
```

### 3. Database Monitoring

```sql
-- Check table sizes
SELECT
    table_name,
    ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb
FROM information_schema.tables
WHERE table_schema = 'ihc_catalog_prod'
ORDER BY size_mb DESC;

-- Check slow queries
SELECT * FROM mysql.slow_log
WHERE sql_text NOT LIKE '%information_schema%'
ORDER BY start_time DESC LIMIT 10;
```

### 4. Performance Optimization

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize Composer autoloader
composer dump-autoload --optimize
```

### 5. Backup Strategy

#### Database Backup

```bash
# Daily database backup
mysqldump -u ihc_user -p ihc_catalog_prod > backup_$(date +%Y%m%d_%H%M%S).sql

# Automated backup script
#!/bin/bash
BACKUP_DIR="/var/backups/ihc-catalog"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u ihc_user -p ihc_catalog_prod > $BACKUP_DIR/db_backup_$DATE.sql
find $BACKUP_DIR -name "db_backup_*.sql" -mtime +7 -delete
```

#### File Backup

```bash
# Backup uploaded files
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

## Troubleshooting

### Common Issues

#### 1. Permission Errors

```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

#### 2. Database Connection Issues

```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

#### 3. Queue Not Processing

```bash
# Check queue status
php artisan queue:status

# Restart queue workers
php artisan queue:restart
```

#### 4. High Memory Usage

```bash
# Check memory usage
ps aux --sort=-%mem | head -10

# Optimize PHP-FPM
# Adjust pm.max_children, pm.start_servers, pm.min_spare_servers, pm.max_spare_servers
```

### Performance Tuning

#### PHP Configuration (`/etc/php/8.2/fpm/php.ini`)

```ini
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 7963
```

#### MySQL Configuration (`/etc/mysql/mysql.conf.d/mysqld.cnf`)

```ini
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 256M
max_connections = 200
```

## Security Checklist

- [ ] SSL/TLS certificate installed and configured
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] Sensitive files (.env, .git) not accessible via web
- [ ] Database credentials are strong and unique
- [ ] JWT secret key is secure and not committed to version control
- [ ] Debug mode disabled (`APP_DEBUG=false`)
- [ ] Security headers properly configured
- [ ] Regular security updates applied
- [ ] Firewall configured to restrict access
- [ ] Failed login attempts monitored
- [ ] Regular backups scheduled and tested

## Rollback Procedure

In case of deployment issues:

```bash
# Quick rollback to previous version
git checkout previous-commit-hash
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate:rollback --step=1
php artisan cache:clear
php artisan config:cache
```

## Support

For deployment issues or questions:
- Check application logs: `storage/logs/laravel-*.log`
- Review web server error logs
- Verify database connectivity
- Test API endpoints with proper authentication

## Version Information

- **Application Version**: IHC Catalog Service v1.0.0
- **Laravel Version**: 12.x
- **PHP Version**: 8.2+
- **Database**: MySQL 8.0+
- **Last Updated**: January 2026
