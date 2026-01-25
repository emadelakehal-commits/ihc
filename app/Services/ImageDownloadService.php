<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageDownloadService
{
    /**
     * Download and organize product images from Google Drive
     */
    public function downloadFromGoogleDrive(string $driveUrl): array
    {
        // Create temporary directory for download
        $tempDir = '/tmp/drive_images_' . time() . '_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            throw new \Exception('Failed to create temporary directory');
        }

        try {
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
                $productFiles = glob("$productDir/*.jpg") + glob("$productDir/*.jpeg") +
                               glob("$productDir/*.png") + glob("$productDir/*.gif") +
                               glob("$productDir/*.bmp") + glob("$productDir/*.webp");
                $imageIndex = 1;

                foreach ($productFiles as $file) {
                    $filename = basename($file);
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Skip files that don't match the expected pattern
                    if (!preg_match('/^' . preg_quote($productCode, '/') . '(_main|_\\d+)?\.' . $extension . '$/', $filename)) {
                        // Rename file to match expected pattern
                        if (strpos($filename, '_main') !== false) {
                            $newFilename = $productCode . '_main.' . $extension;
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
                $variantDirs = glob("$productDir/variant/*", GLOB_ONLYDIR) +
                              glob("$productDir/Variant/*", GLOB_ONLYDIR);
                foreach ($variantDirs as $variantDir) {
                    $isku = basename($variantDir);

                    // Create variant directory
                    Storage::disk('public')->makeDirectory("products/$productCode/variant/$isku");

                    // Process variant images
                    $variantFiles = glob("$variantDir/*.jpg") + glob("$variantDir/*.jpeg") +
                                   glob("$variantDir/*.png") + glob("$variantDir/*.gif") +
                                   glob("$variantDir/*.bmp") + glob("$variantDir/*.webp");
                    $variantImageIndex = 1;

                    foreach ($variantFiles as $file) {
                        $filename = basename($file);
                        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                        // Skip files that don't match the expected pattern
                        if (!preg_match('/^' . preg_quote($isku, '/') . '(_main|_\\d+)?\.' . $extension . '$/', $filename)) {
                            // Rename file to match expected pattern
                            if (strpos($filename, '_main') !== false) {
                                $newFilename = $isku . '_main.' . $extension;
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
            if (file_exists($tempDir)) {
                shell_exec("rm -rf \"$tempDir\"");
            }
            throw $e;
        }
    }

    /**
     * Get product images for a given product code
     */
    public function getProductImages(string $productCode): array
    {
        $images = [];
        $productDir = "products/{$productCode}";

        if (Storage::disk('public')->exists($productDir)) {
            $files = Storage::disk('public')->files($productDir);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                // Check if filename matches pattern: productcode_main or productcode_number
                if (preg_match('/^' . preg_quote($productCode, '/') . '(_main|_\\d+)?$/', $filename)) {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($extension, $imageExtensions)) {
                        $images[] = asset("storage/{$file}");
                    }
                }
            }
        }

        return $images;
    }

    /**
     * Get product item images for a given product code and ISKU
     */
    public function getProductItemImages(string $productCode, string $isku): array
    {
        $images = [];
        $productItemDir = "products/{$productCode}/variant/{$isku}";

        if (Storage::disk('public')->exists($productItemDir)) {
            $files = Storage::disk('public')->files($productItemDir);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                // Check if filename matches pattern: isku_main or isku_number
                if (preg_match('/^' . preg_quote($isku, '/') . '(_main|_\\d+)?$/', $filename)) {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($extension, $imageExtensions)) {
                        $images[] = asset("storage/{$file}");
                    }
                }
            }
        }

        // If no variant images found, fallback to product images
        if (empty($images)) {
            $images = $this->getProductImages($productCode);
        }

        return $images;
    }
}
