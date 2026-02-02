# Storage Directories Setup Guide

This document explains how to ensure all required storage directories exist for the IHC project, especially important for deployment scenarios where directories are not tracked in Git.

## Problem

The following directories in `storage/app/public/` are typically ignored by Git (via `.gitignore`) but are required for the application to function properly:

- `categories/` - For category images and banners
- `excel/` - For Excel file uploads and processing
- `images/` - For product images and media files
- `product-documents/` - For PDFs and product documentation
- `products/` - For additional product-related files

When cloning a fresh copy of the project, these directories won't exist, which can cause file upload and storage operations to fail.

## Solutions

### 1. Automatic Directory Creation in Database Setup Commands

Two database setup commands have been enhanced to automatically create these directories:

#### `php artisan ihc:setup-database`
This command creates the database, runs migrations, seeds lookup tables, and ensures storage directories exist.

#### `php artisan ihc:setup-fresh-database`
This command drops all tables, recreates the database from scratch, runs migrations, seeds lookup tables, and ensures storage directories exist.

### 2. Dedicated Directory Creation Script

#### PHP Script
```bash
php scripts/ensure-storage-directories.php
```

#### Shell Script
```bash
./scripts/ensure-storage-directories.sh
```

Both scripts will:
- Create all required directories if they don't exist
- Set proper permissions (755)
- Provide feedback on what was created

## Usage in Deployment

### Option 1: Use Database Setup Commands (Recommended)
```bash
# For initial setup
php artisan ihc:setup-database

# For fresh setup (drops existing data)
php artisan ihc:setup-fresh-database --seed
```

### Option 2: Use Dedicated Scripts
```bash
# Using PHP script
php scripts/ensure-storage-directories.php

# Using shell script
./scripts/ensure-storage-directories.sh
```

### Option 3: Manual Directory Creation
```bash
mkdir -p storage/app/public/categories
mkdir -p storage/app/public/excel
mkdir -p storage/app/public/images
mkdir -p storage/app/public/product-documents
mkdir -p storage/app/public/products
chmod -R 755 storage/app/public
```

## Implementation Details

### Database Commands Enhancement

Both `SetupIhcDatabase` and `SetupFreshDatabase` commands now include:

1. **Import Statement**: Added `use Illuminate\Support\Facades\Storage;`
2. **Method Call**: Added `$this->ensureStorageDirectoriesExist();` at the end of the `handle()` method
3. **Implementation**: Added `ensureStorageDirectoriesExist()` method that:
   - Defines the required directories
   - Checks if each directory exists using Laravel's Storage facade
   - Creates directories if they don't exist with proper permissions
   - Provides feedback on creation status

### Scripts

#### PHP Script (`scripts/ensure-storage-directories.php`)
- Bootstraps the Laravel application
- Uses Laravel's Storage facade for consistent file operations
- Provides detailed output about directory creation

#### Shell Script (`scripts/ensure-storage-directories.sh`)
- Uses standard Unix commands (`mkdir`, `chmod`)
- No Laravel dependency required
- Suitable for deployment scripts and CI/CD pipelines

## Best Practices

1. **Always run directory creation during deployment** - Include one of the scripts in your deployment process
2. **Use database setup commands when setting up the database** - They will automatically handle directory creation
3. **Check directory permissions** - Ensure the web server has write permissions to these directories
4. **Monitor for errors** - Check logs if file upload operations fail

## Troubleshooting

### Directory Creation Fails
- Check that the `storage/app/public` directory exists and is writable
- Verify that the web server user has appropriate permissions
- Check disk space availability

### File Upload Operations Fail
- Verify directories exist: `ls -la storage/app/public/`
- Check permissions: `ls -la storage/app/public/` (should show 755 or similar)
- Ensure web server can write to the directories

### Laravel Storage Operations Fail
- Verify that the storage link exists: `php artisan storage:link`
- Check that the storage disk configuration is correct in `config/filesystems.php`

## Integration with Deployment

### Example Deployment Script
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
./scripts/ensure-storage-directories.sh

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment completed successfully!"
```

This ensures that storage directories are always available after deployment, preventing file upload and storage issues.