<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductImageDownloadService
{
    /**
     * Download and organize product images from Google Drive.
     */
    public function downloadProductImages(string $driveUrl): array
    {
        try {
            // Create temporary directory for download
            $tempDir = '/tmp/drive_images_' . time();
            if (!mkdir($tempDir, 0755, true)) {
                throw new \Exception('Failed to create temporary directory');
            }

            // Execute gdown command to download the folder
            $command = "gdown --folder \"$driveUrl\" -O \"$tempDir\" 2>&1";
            $output = shell_exec($command);

            if ($output === null) {
                throw new \Exception('Failed to execute download command');
            }

            // Check if download was successful by looking for downloaded files
            $downloadedFiles = [];
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempDir));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $downloadedFiles[] = $file->getPathname();
                }
            }

            if (empty($downloadedFiles)) {
                // Clean up temp directory
                shell_exec("rm -rf \"$tempDir\"");
                throw new \Exception('No files were downloaded from Google Drive');
            }

            $processedProducts = 0;
            $processedImages = 0;

            // Process each product directory
            $productDirs = glob("$tempDir/*", GLOB_ONLYDIR);
            foreach ($productDirs as $productDir) {
                $productCode = basename($productDir);

                // Create product directory in storage
                Storage::disk('public')->makeDirectory("products/$productCode");

                // Process product images (files directly in product directory)
                $productFiles = glob("$productDir/*.jpg") + glob("$productDir/*.jpeg") + glob("$productDir/*.png") + glob("$productDir/*.gif") + glob("$productDir/*.bmp") + glob("$productDir/*.webp");
                $imageIndex = 1;

                foreach ($productFiles as $file) {
                    $filename = basename($file);
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Skip files that don't match the expected pattern
                    if (!preg_match('/^' . preg_quote($productCode, '/') . '(_Main|_\\d+)?\.' . $extension . '$/', $filename)) {
                        // Rename file to match expected pattern
                        if (strpos($filename, '_Main') !== false) {
                            $newFilename = $productCode . '_Main.' . $extension;
                        } else {
                            $newFilename = $productCode . '_' . $imageIndex . '.' . $extension;
                            $imageIndex++;
                        }

                        $newPath = dirname($file) . '/' . $newFilename;
                        rename($file, $newPath);
                        $file = $newPath;
                        $filename = $newFilename;
                    }

                    // Copy to storage
                    $storagePath = "products/$productCode/$filename";
                    if (Storage::disk('public')->put($storagePath, file_get_contents($file))) {
                        $processedImages++;
                    }
                }

                // Process variant directories (product item images)
                $variantDirs = glob("$productDir/variant/*", GLOB_ONLYDIR) + glob("$productDir/Variant/*", GLOB_ONLYDIR);
                foreach ($variantDirs as $variantDir) {
                    $isku = basename($variantDir);

                    // Create variant directory
                    Storage::disk('public')->makeDirectory("products/$productCode/variant/$isku");

                    // Process variant images
                    $variantFiles = glob("$variantDir/*.jpg") + glob("$variantDir/*.jpeg") + glob("$variantDir/*.png") + glob("$variantDir/*.gif") + glob("$variantDir/*.bmp") + glob("$variantDir/*.webp");
                    $variantImageIndex = 1;

                    foreach ($variantFiles as $file) {
                        $filename = basename($file);
                        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                        // Skip files that don't match the expected pattern
                        if (!preg_match('/^' . preg_quote($isku, '/') . '(_Main|_\\d+)?\.' . $extension . '$/', $filename)) {
                            // Rename file to match expected pattern
                            if (strpos($filename, '_Main') !== false) {
                                $newFilename = $isku . '_Main.' . $extension;
                            } else {
                                $newFilename = $isku . '_' . $variantImageIndex . '.' . $extension;
                                $variantImageIndex++;
                            }

                            $newPath = dirname($file) . '/' . $newFilename;
                            rename($file, $newPath);
                            $file = $newPath;
                            $filename = $newFilename;
                        }

                        // Copy to storage
                        $storagePath = "products/$productCode/variant/$isku/$filename";
                        if (Storage::disk('public')->put($storagePath, file_get_contents($file))) {
                            $processedImages++;
                        }
                    }
                }

                $processedProducts++;
            }

            // Clean up temporary directory
            shell_exec("rm -rf \"$tempDir\"");

            return [
                'products_processed' => $processedProducts,
                'images_processed' => $processedImages,
                'drive_url' => $driveUrl
            ];

        } catch (\Exception $e) {
            // Clean up temp directory on error
            if (isset($tempDir) && file_exists($tempDir)) {
                shell_exec("rm -rf \"$tempDir\"");
            }

            Log::error('Error downloading images from Google Drive', [
                'driveUrl' => $driveUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error downloading images from Google Drive: ' . $e->getMessage());
        }
    }
}