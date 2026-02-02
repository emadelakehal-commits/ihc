# Logging and Storage Setup Guide

This document explains the comprehensive logging and storage setup for the IHC project, including rotational logging and automatic directory creation.

## 🔄 Rotational Logging Implementation

### Overview
The application now uses rotational logging to prevent log files from growing indefinitely and consuming disk space. This ensures better performance and easier log management.

### Default Log Channel
- **Changed from**: `single` (one file that grows indefinitely)
- **Changed to**: `daily` (automatic rotation with 14-day retention)

### Specialized Log Channels

#### 1. API Logs (`api` channel)
- **File**: `storage/logs/api-YYYY-MM-DD.log`
- **Retention**: 30 days
- **Purpose**: API request/response logging with detailed information
- **Level**: `info` and above

#### 2. Error Logs (`error` channel)
- **File**: `storage/logs/error-YYYY-MM-DD.log`
- **Retention**: 90 days
- **Purpose**: Application errors and exceptions
- **Level**: `error` and above

#### 3. System Logs (`system` channel)
- **File**: `storage/logs/system-YYYY-MM-DD.log`
- **Retention**: 60 days
- **Purpose**: System events and operations
- **Level**: `info` and above

#### 4. Query Logs (`query` channel)
- **File**: `storage/logs/query-YYYY-MM-DD.log`
- **Retention**: 7 days
- **Purpose**: Database query logging for debugging
- **Level**: `debug` and above

#### 5. Security Logs (`security` channel)
- **File**: `storage/logs/security-YYYY-MM-DD.log`
- **Retention**: 365 days
- **Purpose**: Security events and authentication logs
- **Level**: `warning` and above

### API Logging Middleware
The `ApiLoggingMiddleware` now logs to the dedicated `api` channel with:
- Request method, URL, IP, and user agent
- Request headers (sanitized)
- Request parameters (sanitized and limited)
- Response status, duration, and size
- Automatic sensitive data redaction

### Configuration
All log retention periods can be configured via environment variables:
- `LOG_API_DAYS=30`
- `LOG_ERROR_DAYS=90`
- `LOG_SYSTEM_DAYS=60`
- `LOG_QUERY_DAYS=7`
- `LOG_SECURITY_DAYS=365`

## 📁 Storage Directories Setup

### Problem
The following directories in `storage/app/public/` are typically ignored by Git but are required for the application to function properly:

- `categories/` - For category images and banners
- `excel/` - For Excel file uploads and processing
- `images/` - For product images and media files
- `product-documents/` - For PDFs and product documentation
- `products/` - For additional product-related files

### Solutions

#### 1. Automatic Directory Creation in Database Setup Commands

**`php artisan ihc:setup-database`**
- Creates database, runs migrations, seeds lookup tables
- **Automatically ensures storage directories exist**
- Uses correct database name from `.env` file

**`php artisan ihc:setup-fresh-database`**
- Drops all tables, recreates database from scratch
- Runs migrations and seeds lookup tables
- **Automatically ensures storage directories exist**
- Uses correct database name from `.env` file

#### 2. Dedicated Directory Creation Command

**`php artisan storage:ensure-directories`**
- Creates all required storage directories
- Provides feedback on creation status
- Can be run independently

#### 3. Deployment Scripts

**Shell Script**: `./scripts/ensure-storage-directories.sh`
- No Laravel dependency required
- Suitable for deployment scripts and CI/CD pipelines

**PHP Script**: `php scripts/ensure-storage-directories.php`
- Uses Laravel's Storage facade
- Provides detailed output

## 🚀 Deployment Integration

### Recommended Deployment Process

```bash
#!/bin/bash
# deployment.sh

# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Ensure storage directories exist
php artisan storage:ensure-directories

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment completed successfully!"
```

### Alternative: Use Database Setup Commands

```bash
# For initial setup
php artisan ihc:setup-database

# For fresh setup (drops existing data)
php artisan ihc:setup-fresh-database --seed
```

## 🔍 Monitoring and Maintenance

### Log File Monitoring
- Check log file sizes: `ls -lh storage/logs/`
- Monitor disk usage: `df -h`
- Review log retention: Files older than retention period are automatically deleted

### Storage Directory Monitoring
- Verify directories exist: `ls -la storage/app/public/`
- Check permissions: `ls -la storage/app/public/` (should show 755 or similar)
- Monitor disk space for uploaded files

### Common Issues and Solutions

#### Log Files Growing Too Large
- **Cause**: Retention period too long or high log volume
- **Solution**: Adjust retention periods in `.env` file

#### Storage Directories Missing
- **Cause**: Fresh deployment without directory creation
- **Solution**: Run `php artisan storage:ensure-directories`

#### File Upload Operations Fail
- **Cause**: Missing directories or incorrect permissions
- **Solution**: Verify directories exist and have proper permissions

#### Database Operations Fail
- **Cause**: Database name mismatch
- **Solution**: Ensure `.env` file has correct `DB_DATABASE` value

## 📊 Log File Examples

### API Log Entry
```
[2026-02-02 20:44:18] local.INFO: API Request {"method":"GET","url":"http://localhost/api/categories","ip":"127.0.0.1","user_agent":"Mozilla/5.0...","headers":{"accept":"application/json","content-type":null,"authorization":"absent"},"params":{"locale":"en"}}
[2026-02-02 20:44:18] local.INFO: API Response {"method":"GET","url":"http://localhost/api/categories","status":200,"duration_ms":15.2,"response_size":2048}
```

### Error Log Entry
```
[2026-02-02 20:44:18] local.ERROR: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'ihc_new.lkp_language' doesn't exist {"exception":"[object] (Illuminate\\Database\\QueryException(code: 42S02): SQLSTATE[42S02]: Base table or view not found: 1146 Table 'ihc_new.lkp_language' doesn't exist at /path/to/project/vendor/laravel/framework/src/Illuminate/Database/Connection.php:760)
```

## 🎯 Best Practices

1. **Always run directory creation during deployment** - Include in deployment scripts
2. **Monitor log file sizes** - Adjust retention periods based on disk space
3. **Use appropriate log levels** - Don't log debug information in production
4. **Secure sensitive data** - The middleware automatically redacts sensitive fields
5. **Regular cleanup** - Rotational logging handles this automatically
6. **Monitor disk usage** - Especially for storage directories with uploaded files

This comprehensive setup ensures both proper logging with rotation and automatic storage directory creation for reliable deployment and operation.