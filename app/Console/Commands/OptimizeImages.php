<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class OptimizeImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize
                            {--quality=80 : JPEG quality percentage (1-100)}
                            {--max-width=1200 : Maximum width in pixels}
                            {--max-height=1200 : Maximum height in pixels}
                            {--dry-run : Show what would be optimized without making changes}
                            {--force : Force optimization even for already optimized images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize all product and category images for faster loading';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $quality = (int) $this->option('quality');
        $maxWidth = (int) $this->option('max-width');
        $maxHeight = (int) $this->option('max-height');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Validate options
        if ($quality < 1 || $quality > 100) {
            $this->error('Quality must be between 1 and 100');
            return 1;
        }

        if ($maxWidth < 1 || $maxHeight < 1) {
            $this->error('Maximum width and height must be greater than 0');
            return 1;
        }

        if ($dryRun) {
            $this->info('DRY RUN MODE - No images will be modified');
        }

        $this->info("Starting image optimization with quality: {$quality}%, max dimensions: {$maxWidth}x{$maxHeight}");

        $optimizedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        // Optimize product images
        $this->info('Optimizing product images...');
        $result = $this->optimizeProductImages($quality, $maxWidth, $maxHeight, $dryRun, $force);
        $optimizedCount += $result['optimized'];
        $skippedCount += $result['skipped'];
        $errorCount += $result['errors'];

        // Optimize category images
        $this->info('Optimizing category images...');
        $result = $this->optimizeCategoryImages($quality, $maxWidth, $maxHeight, $dryRun, $force);
        $optimizedCount += $result['optimized'];
        $skippedCount += $result['skipped'];
        $errorCount += $result['errors'];

        // Summary
        $this->info('Optimization complete!');
        $this->info("Optimized: {$optimizedCount} images");
        $this->info("Skipped: {$skippedCount} images");
        if ($errorCount > 0) {
            $this->error("Errors: {$errorCount} images");
        }

        return 0;
    }

    /**
     * Optimize all product images
     */
    private function optimizeProductImages(int $quality, int $maxWidth, int $maxHeight, bool $dryRun, bool $force): array
    {
        $optimized = 0;
        $skipped = 0;
        $errors = 0;

        // Get all product directories
        $productDirs = Storage::disk('public')->directories('products');

        foreach ($productDirs as $productDir) {
            $productCode = basename($productDir);
            $this->info("Processing product: {$productCode}");

            // Get all image files in product directory
            $imageFiles = $this->getImageFilesInDirectory($productDir);

            foreach ($imageFiles as $imageFile) {
                $result = $this->optimizeImageFile($imageFile, $quality, $maxWidth, $maxHeight, $dryRun, $force);
                if ($result === 'optimized') {
                    $optimized++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                } elseif ($result === 'error') {
                    $errors++;
                }
            }

            // Process variant directories
            $variantDirs = Storage::disk('public')->directories($productDir);
            foreach ($variantDirs as $variantDir) {
                $variantFiles = $this->getImageFilesInDirectory($variantDir);
                foreach ($variantFiles as $imageFile) {
                    $result = $this->optimizeImageFile($imageFile, $quality, $maxWidth, $maxHeight, $dryRun, $force);
                    if ($result === 'optimized') {
                        $optimized++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    } elseif ($result === 'error') {
                        $errors++;
                    }
                }
            }
        }

        return ['optimized' => $optimized, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Optimize all category images
     */
    private function optimizeCategoryImages(int $quality, int $maxWidth, int $maxHeight, bool $dryRun, bool $force): array
    {
        $optimized = 0;
        $skipped = 0;
        $errors = 0;

        // Get all category image files
        $categoryFiles = $this->getImageFilesInDirectory('categories');

        foreach ($categoryFiles as $imageFile) {
            $result = $this->optimizeImageFile($imageFile, $quality, $maxWidth, $maxHeight, $dryRun, $force);
            if ($result === 'optimized') {
                $optimized++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } elseif ($result === 'error') {
                $errors++;
            }
        }

        return ['optimized' => $optimized, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Get all image files in a directory
     */
    private function getImageFilesInDirectory(string $directory): array
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $allFiles = Storage::disk('public')->files($directory);
        
        return array_filter($allFiles, function($file) use ($imageExtensions) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, $imageExtensions);
        });
    }

    /**
     * Optimize a single image file
     */
    private function optimizeImageFile(string $imagePath, int $quality, int $maxWidth, int $maxHeight, bool $dryRun, bool $force): string
    {
        try {
            // Get original file size
            $originalSize = Storage::disk('public')->size($imagePath);
            
            // Skip if already optimized (unless force is used)
            if (!$force && $this->isAlreadyOptimized($imagePath, $quality, $maxWidth, $maxHeight)) {
                $this->line("  Skipped: {$imagePath} (already optimized)");
                return 'skipped';
            }

            // Get image content
            $imageContent = Storage::disk('public')->get($imagePath);
            
            // Create image manager and read image
            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageContent);
            
            // Calculate new dimensions
            $width = $image->width();
            $height = $image->height();
            
            // Resize if needed
            if ($width > $maxWidth || $height > $maxHeight) {
                $image->scaleDown($maxWidth, $maxHeight);
            }

            // Optimize and save
            $optimizedContent = $this->optimizeImageContent($image, $quality);
            $optimizedSize = strlen($optimizedContent);
            
            if ($dryRun) {
                $reduction = $originalSize > 0 ? round((($originalSize - $optimizedSize) / $originalSize) * 100, 1) : 0;
                $this->line("  Would optimize: {$imagePath} ({$this->formatBytes($originalSize)} → {$this->formatBytes($optimizedSize)} - {$reduction}% reduction)");
                return 'optimized'; // Count as optimized in dry run
            } else {
                // Save optimized image
                Storage::disk('public')->put($imagePath, $optimizedContent);
                
                $reduction = $originalSize > 0 ? round((($originalSize - $optimizedSize) / $originalSize) * 100, 1) : 0;
                $this->line("  Optimized: {$imagePath} ({$this->formatBytes($originalSize)} → {$this->formatBytes($optimizedSize)} - {$reduction}% reduction)");
                return 'optimized';
            }
        } catch (\Exception $e) {
            $this->error("  Error optimizing {$imagePath}: " . $e->getMessage());
            Log::error("Image optimization failed for {$imagePath}: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Check if image is already optimized
     */
    private function isAlreadyOptimized(string $imagePath, int $quality, int $maxWidth, int $maxHeight): bool
    {
        try {
            $imageContent = Storage::disk('public')->get($imagePath);
            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageContent);
            
            // Check if dimensions are already within limits
            $width = $image->width();
            $height = $image->height();
            
            if ($width <= $maxWidth && $height <= $maxHeight) {
                // Check if file size suggests it's already optimized
                $fileSize = Storage::disk('public')->size($imagePath);
                $estimatedOptimalSize = ($width * $height * 3) / 10; // Rough estimate for optimized JPEG
                
                // If file size is close to optimal, consider it optimized
                return $fileSize < ($estimatedOptimalSize * 1.5);
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize image content based on format
     */
    private function optimizeImageContent($image, int $quality): string
    {
        // For v3, we'll default to JPEG optimization for all images
        // This provides the best compression for most use cases
        return $image->toJpeg($quality)->toString();
    }

    /**
     * Check if image is photo-like (has gradients, not flat colors)
     */
    private function isPhotoLike($image): bool
    {
        // Simple heuristic: photos usually have more colors and gradients
        // This is a basic check - could be improved with more sophisticated analysis
        $width = $image->width();
        $height = $image->height();
        
        // Sample a few pixels to check for color variation
        $colors = [];
        for ($x = 0; $x < $width; $x += max(1, $width / 10)) {
            for ($y = 0; $y < $height; $y += max(1, $height / 10)) {
                $color = $image->pickColor($x, $y);
                $colorKey = $color->toString();
                $colors[$colorKey] = true;
            }
        }
        
        // If we have many different colors, it's likely a photo
        return count($colors) > 10;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 1) . ' ' . $units[$unitIndex];
    }
}