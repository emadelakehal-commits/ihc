<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Http\UploadedFile;
class ZipExtractionService
{
    /**
     * Extract zip file and validate structure
     */
    public function extractZipFile($zipFile): array
    {
        $tempDir = storage_path('app/temp/zip-extraction-' . Str::random(10));
        $publicProductsPath = storage_path('app/public/products');
        
        // Ensure public products directory exists
        if (!file_exists($publicProductsPath)) {
            mkdir($publicProductsPath, 0755, true);
        }

        try {
            // Create temporary directory
            if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                throw new \Exception('Failed to create temporary directory');
            }

            // Extract zip file
            $zip = new ZipArchive();
            if ($zip->open($zipFile->getRealPath()) !== true) {
                throw new \Exception('Failed to open zip file');
            }

            if (!$zip->extractTo($tempDir)) {
                $zip->close();
                throw new \Exception('Failed to extract zip file');
            }
            $zip->close();
            
            // Debug: Check what files were actually extracted
            $extractedFiles = glob($tempDir . '/**/*', GLOB_BRACE);
            Log::info('Files extracted from zip', [
                'tempDir' => $tempDir,
                'extracted_files' => $extractedFiles,
                'file_count' => count($extractedFiles)
            ]);

            // Validate and process extracted files
            $result = $this->processExtractedFiles($tempDir, $publicProductsPath);

            // Clean up temporary directory
            $this->deleteDirectory($tempDir);

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (\Exception $e) {
            // Clean up on error
            if (file_exists($tempDir)) {
                $this->deleteDirectory($tempDir);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process extracted files and validate structure
     */
    private function processExtractedFiles(string $tempDir, string $publicProductsPath): array
    {
        $processedProducts = [];
        $errors = [];

        // Get all directories in the temp directory (these are the content folders from the zip)
        $contentDirs = array_filter(glob($tempDir . '/*'), function($path) {
            return is_dir($path);
        });

        // If no directories found, check if we have files at root level that should be treated as a single product
        if (empty($contentDirs)) {
            $rootFiles = glob($tempDir . '/*');
            if (!empty($rootFiles)) {
                // Create a temporary directory to hold root-level files
                $tempProductDir = $tempDir . '/temp_product_' . uniqid();
                mkdir($tempProductDir, 0755, true);
                
                // Move all root files into the temp directory
                foreach ($rootFiles as $file) {
                    if (is_file($file)) {
                        $filename = basename($file);
                        copy($file, $tempProductDir . '/' . $filename);
                    }
                }
                
                $contentDirs = [$tempProductDir];
            }
        }

        // If we have only one directory at the root level, check if it's just a wrapper directory
        // and look for directories inside it instead
        if (count($contentDirs) === 1) {
            $singleDir = $contentDirs[0];
            $innerDirs = array_filter(glob($singleDir . '/*'), function($path) {
                return is_dir($path);
            });
            
            // If there are directories inside the single directory, use those instead
            if (!empty($innerDirs)) {
                $contentDirs = $innerDirs;
                Log::info('Found wrapper directory, using inner directories instead', [
                    'wrapper_dir' => $singleDir,
                    'inner_dirs' => $innerDirs
                ]);
            }
        }

        Log::info('Content directories found', [
            'tempDir' => $tempDir,
            'content_dirs' => $contentDirs,
            'dir_count' => count($contentDirs)
        ]);

        foreach ($contentDirs as $contentDir) {
            try {
                // Get the directory name (this will be our product code or folder name)
                $dirName = basename($contentDir);
                
                Log::info('Processing content directory', ['dir_name' => $dirName, 'dir_path' => $contentDir]);

                // Copy the entire directory structure to the products directory
                $targetDir = $publicProductsPath . '/' . $dirName;
                
                // Use recursive copy to preserve the entire directory structure
                $this->copyDirectoryRecursively($contentDir, $targetDir);

                // Log the successful copy
                Log::info('Successfully copied directory', [
                    'source' => $contentDir,
                    'target' => $targetDir
                ]);

                // Add to processed products
                $processedProducts[] = [
                    'product_code' => $dirName,
                    'main_images' => [],
                    'variant_images' => [],
                    'errors' => []
                ];

            } catch (\Exception $e) {
                $errors[] = "Error processing directory {$contentDir}: " . $e->getMessage();
            }
        }

        return [
            'processed_products' => $processedProducts,
            'total_errors' => count($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate product directory structure
     */
    private function validateProductStructure(string $productDir): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'main_images' => [],
            'variants' => []
        ];

        // Check for main product images (root level)
        $mainImages = glob($productDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        $result['main_images'] = $mainImages;

        // Check for variant directories
        $variantDirs = array_filter(glob($productDir . '/*'), function($path) {
            return is_dir($path) && basename($path) !== 'variant';
        });

        foreach ($variantDirs as $variantDir) {
            $variantCode = basename($variantDir);
            $variantImages = glob($variantDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            
            if (!empty($variantImages)) {
                $result['variants'][$variantCode] = $variantImages;
            }
        }

        // Validate naming conventions
        foreach ($mainImages as $imagePath) {
            $filename = basename($imagePath);
            $productCode = basename($productDir);
            
            if (!$this->isValidMainImageName($filename, $productCode)) {
                $result['errors'][] = "Invalid main image name: {$filename}. Expected format: {$productCode}_Main.ext or {$productCode}_n.ext";
                $result['valid'] = false;
            }
        }

        foreach ($result['variants'] as $variantCode => $variantImages) {
            foreach ($variantImages as $imagePath) {
                $filename = basename($imagePath);
                
                if (!$this->isValidVariantImageName($filename, $variantCode)) {
                    $result['errors'][] = "Invalid variant image name: {$filename}. Expected format: {$variantCode}_Main.ext or {$variantCode}_n.ext";
                    $result['valid'] = false;
                }
            }
        }

        return $result;
    }

    /**
     * Validate main product image naming
     */
    private function isValidMainImageName(string $filename, string $productCode): bool
    {
        // Pattern: productcode_Main or productcode_main or productcode_n.ext where n is a number
        $pattern = "/^{$productCode}(_Main|_main|\_\d+)\.(jpg|jpeg|png|gif|webp)$/i";
        return preg_match($pattern, $filename) === 1;
    }

    /**
     * Validate variant image naming
     */
    private function isValidVariantImageName(string $filename, string $variantCode): bool
    {
        // Pattern: variantcode_Main or variantcode_main or variantcode_n.ext where n is a number
        $pattern = "/^{$variantCode}(_Main|_main|\_\d+)\.(jpg|jpeg|png|gif|webp)$/i";
        return preg_match($pattern, $filename) === 1;
    }

    /**
     * Process and move main product image
     */
    private function processMainImage(string $imagePath, string $productCode, string $publicProductsPath): array
    {
        try {
            $filename = basename($imagePath);
            $targetDir = $publicProductsPath . '/' . $productCode;
            
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . '/' . $filename;
            
            // Copy file to target location
            if (!copy($imagePath, $targetPath)) {
                throw new \Exception("Failed to copy image to {$targetPath}");
            }

            return [
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'path' => $targetPath,
                    'url' => asset("storage/products/{$productCode}/{$filename}")
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process and move variant image
     */
    private function processVariantImage(string $imagePath, string $productCode, string $variantCode, string $publicProductsPath): array
    {
        try {
            $filename = basename($imagePath);
            $targetDir = $publicProductsPath . '/' . $productCode . '/variant/' . $variantCode;
            
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . '/' . $filename;
            
            // Copy file to target location
            if (!copy($imagePath, $targetPath)) {
                throw new \Exception("Failed to copy image to {$targetPath}");
            }

            return [
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'path' => $targetPath,
                    'url' => asset("storage/products/{$productCode}/variant/{$variantCode}/{$filename}")
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract product code from filename
     */
    private function extractProductCodeFromFilename(string $filename): ?string
    {
        // Pattern to match product code at the beginning of filename
        // Supports: productcode_Main.ext, productcode_main.ext, productcode_1.ext, etc.
        $pattern = '/^([A-Za-z0-9]+)(_Main|_main|\_\d+)?\.(jpg|jpeg|png|gif|webp)$/i';
        
        if (preg_match($pattern, $filename, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Extract variant code from filename
     */
    private function extractVariantCodeFromFilename(string $filename): ?string
    {
        // Pattern to match variant code at the beginning of filename
        // Supports: variantcode_Main.ext, variantcode_main.ext, variantcode_1.ext, etc.
        $pattern = '/^([A-Za-z0-9]+)(_Main|_main|\_\d+)?\.(jpg|jpeg|png|gif|webp)$/i';
        
        if (preg_match($pattern, $filename, $matches)) {
            $potentialCode = $matches[1];
            
            // Check if this looks like a variant code (typically longer or contains specific patterns)
            // For now, we'll assume any code that doesn't match known product patterns is a variant
            // This is a simplified approach - in a real system you might have a list of known product codes
            
            return $potentialCode;
        }
        
        return null;
    }

    /**
     * Find product index in processed products array
     */
    private function findProductIndex(array $processedProducts, string $productCode): int
    {
        foreach ($processedProducts as $index => $product) {
            if ($product['product_code'] === $productCode) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Find variant index in variant images array
     */
    private function findVariantIndex(array $variantImages, string $variantCode): int
    {
        foreach ($variantImages as $index => $variant) {
            if ($variant['variant_code'] === $variantCode) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Find all image files recursively in a directory
     */
    private function findImageFilesRecursively(string $directory): array
    {
        $imageFiles = [];
        $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $supportedExtensions)) {
                    $imageFiles[] = $file->getPathname();
                }
            }
        }

        return $imageFiles;
    }

    /**
     * Recursively copy directory and all its contents
     */
    private function copyDirectoryRecursively(string $source, string $destination): bool
    {
        if (!file_exists($source)) {
            return false;
        }

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . '/' . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!file_exists($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                if (!copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Recursively delete directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}