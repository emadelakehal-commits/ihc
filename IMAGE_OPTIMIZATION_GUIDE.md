# Image Optimization Guide

This guide explains how to use the image optimization command to reduce image file sizes for faster loading.

## Overview

The `images:optimize` Artisan command automatically optimizes all product and category images in your application to reduce file sizes while maintaining visual quality.

## Usage

### Basic Optimization

```bash
php artisan images:optimize
```

This will optimize all images with default settings:
- Quality: 80%
- Maximum width: 1200px
- Maximum height: 1200px

### Custom Quality and Dimensions

```bash
php artisan images:optimize --quality=70 --max-width=1000 --max-height=1000
```

### Dry Run (Preview Only)

To see what would be optimized without making changes:

```bash
php artisan images:optimize --dry-run
```

### Force Optimization

To optimize images even if they appear to already be optimized:

```bash
php artisan images:optimize --force
```

## Command Options

| Option | Default | Description |
|--------|---------|-------------|
| `--quality` | 80 | JPEG quality percentage (1-100) |
| `--max-width` | 1200 | Maximum width in pixels |
| `--max-height` | 1200 | Maximum height in pixels |
| `--dry-run` | false | Show what would be optimized without making changes |
| `--force` | false | Force optimization even for already optimized images |

## What the Command Does

1. **Scans all product images** in `storage/app/public/products/`
2. **Scans all category images** in `storage/app/public/categories/`
3. **Resizes images** that exceed the maximum dimensions while maintaining aspect ratio
4. **Compresses images** using the specified quality setting
5. **Converts PNG to JPEG** for photo-like images to reduce file size
6. **Preserves PNG format** for graphics and logos
7. **Provides detailed output** showing optimization results

## Image Processing Logic

- **Photos**: Converted to JPEG with specified quality for optimal compression
- **Graphics/Logos**: Kept as PNG to preserve sharp edges and transparency
- **Large images**: Resized to fit within maximum dimensions
- **Already optimized**: Skipped unless `--force` is used

## Example Output

```
Starting image optimization with quality: 80%, max dimensions: 1200x1200
Optimizing product images...
Processing product: PROD001
  Optimized: products/PROD001/PROD001_Main.jpg (2.1 MB → 450 KB - 78.6% reduction)
  Skipped: products/PROD001/PROD001_1.jpg (already optimized)
Processing product: PROD002
  Optimized: products/PROD002/PROD002_Main.jpg (1.8 MB → 380 KB - 78.9% reduction)

Optimizing category images...
  Optimized: categories/SPA_ZONE_0.jpg (1.5 MB → 320 KB - 78.7% reduction)

Optimization complete!
Optimized: 3 images
Skipped: 1 images
```

## Best Practices

1. **Run during maintenance windows** as it processes many files
2. **Use dry-run first** to preview the impact
3. **Adjust quality settings** based on your needs:
   - 80% quality: Good balance of size and quality
   - 70% quality: Smaller files, slightly reduced quality
   - 90% quality: Larger files, higher quality
4. **Monitor results** to ensure visual quality meets your standards
5. **Backup important images** before running if needed

## Performance Impact

- **Storage space**: Significant reduction in storage usage
- **Loading speed**: Faster page loads due to smaller image files
- **Bandwidth**: Reduced bandwidth usage for image delivery
- **Processing time**: Command runs quickly for most image collections

## Troubleshooting

### Command Not Found

If you get "Command not found" error:
1. Ensure the command is registered in `routes/console.php`
2. Run `php artisan list` to see available commands
3. Clear Laravel cache: `php artisan cache:clear`

### Image Processing Errors

If images fail to process:
1. Check file permissions on storage directories
2. Verify Intervention Image package is installed
3. Check Laravel logs for specific error details
4. Ensure GD or Imagick extension is available

### Quality Issues

If images look too compressed:
1. Increase quality setting (try 85-90%)
2. Use `--dry-run` to test different quality levels
3. Consider keeping original images for high-quality requirements