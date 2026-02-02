<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageDownloadService
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // No initialization needed for basic image service
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
