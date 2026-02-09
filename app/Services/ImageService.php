<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageService
{
    /**
     * Get product item images with priority logic
     * 
     * @param string $productCode
     * @param string $isku
     * @return array Array of image URLs
     */
    public function getProductItemImages(string $productCode, string $isku): array
    {
        $imageUrls = [];
        
        $productItemDir = "products/{$productCode}/variant/{$isku}";
        
        if (Storage::disk('public')->exists($productItemDir)) {
            $files = Storage::disk('public')->files($productItemDir);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            
            // Sort files to prioritize _Main images first
            usort($files, function($a, $b) use ($isku) {
                $filenameA = pathinfo($a, PATHINFO_FILENAME);
                $filenameB = pathinfo($b, PATHINFO_FILENAME);
                
                $isMainA = preg_match('/^' . preg_quote($isku, '/') . '_Main$/', $filenameA);
                $isMainB = preg_match('/^' . preg_quote($isku, '/') . '_Main$/', $filenameB);
                
                // If A is _Main and B is not, A comes first
                if ($isMainA && !$isMainB) return -1;
                // If B is _Main and A is not, B comes first  
                if (!$isMainA && $isMainB) return 1;
                
                // If both are _Main or both are not _Main, sort by filename
                return strcmp($filenameA, $filenameB);
            });
            
            // Process files in priority order
            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                // Check if filename matches pattern: isku_Main or isku_number
                if ((preg_match('/^' . preg_quote($isku, '/') . '_Main$/', $filename) && in_array($extension, $imageExtensions)) ||
                    (preg_match('/^' . preg_quote($isku, '/') . '_\\d+$/', $filename) && in_array($extension, $imageExtensions))) {
                    $imageUrls[] = asset("storage/{$file}");
                }
            }
        }

        // If no variant images found, fallback to product images
        if (empty($imageUrls)) {
            $imageUrls = $this->getProductImages($productCode);
        }

        return $imageUrls;
    }

    /**
     * Get product images with priority logic
     * 
     * @param string $productCode
     * @return array Array of image URLs
     */
    public function getProductImages(string $productCode): array
    {
        $imageUrls = [];
        $productDir = "products/{$productCode}";
        
        if (Storage::disk('public')->exists($productDir)) {
            $files = Storage::disk('public')->files($productDir);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            
            // Sort files to prioritize _Main images first
            usort($files, function($a, $b) use ($productCode) {
                $filenameA = pathinfo($a, PATHINFO_FILENAME);
                $filenameB = pathinfo($b, PATHINFO_FILENAME);
                
                $isMainA = preg_match('/^' . preg_quote($productCode, '/') . '_Main$/', $filenameA);
                $isMainB = preg_match('/^' . preg_quote($productCode, '/') . '_Main$/', $filenameB);
                
                // If A is _Main and B is not, A comes first
                if ($isMainA && !$isMainB) return -1;
                // If B is _Main and A is not, B comes first  
                if (!$isMainA && $isMainB) return 1;
                
                // If both are _Main or both are not _Main, sort by filename
                return strcmp($filenameA, $filenameB);
            });
            
            // Process files in priority order
            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                // Check if filename matches pattern: productcode_Main or productcode_number
                if ((preg_match('/^' . preg_quote($productCode, '/') . '_Main$/', $filename) && in_array($extension, $imageExtensions)) ||
                    (preg_match('/^' . preg_quote($productCode, '/') . '_\\d+$/', $filename) && in_array($extension, $imageExtensions))) {
                    $imageUrls[] = asset("storage/{$file}");
                }
            }
        }

        return $imageUrls;
    }

    /**
     * Get single product image URL with priority logic
     * 
     * @param string $productCode
     * @return string|null Single image URL or null
     */
    public function getSingleProductImageUrl(string $productCode): ?string
    {
        $imageUrls = $this->getProductImages($productCode);
        return $imageUrls[0] ?? null;
    }

    /**
     * Get single product item image URL with priority logic
     * 
     * @param string $productCode
     * @param string $isku
     * @return string|null Single image URL or null
     */
    public function getSingleProductItemImageUrl(string $productCode, string $isku): ?string
    {
        $imageUrls = $this->getProductItemImages($productCode, $isku);
        return $imageUrls[0] ?? null;
    }

    /**
     * Get product item image URL for related products (returns single URL)
     * 
     * @param string $productCode
     * @param string $isku
     * @return string|null Single image URL or null
     */
    public function getProductItemImageUrlForRelated(string $productCode, string $isku): ?string
    {
        return $this->getSingleProductItemImageUrl($productCode, $isku);
    }

    /**
     * Get product image URL for related products (returns single URL)
     * 
     * @param string $productCode
     * @return string|null Single image URL or null
     */
    public function getProductImageUrlForRelated(string $productCode): ?string
    {
        return $this->getSingleProductImageUrl($productCode);
    }

    /**
     * Get product item image URL for categories (returns single URL)
     * 
     * @param string $productCode
     * @param string $isku
     * @return string|null Single image URL or null
     */
    public function getProductItemImageUrlForCategories(string $productCode, string $isku): ?string
    {
        return $this->getSingleProductItemImageUrl($productCode, $isku);
    }

    /**
     * Get product image URL for categories (returns single URL)
     * 
     * @param string $productCode
     * @return string|null Single image URL or null
     */
    public function getProductImageUrlForCategories(string $productCode): ?string
    {
        return $this->getSingleProductImageUrl($productCode);
    }

    /**
     * Get product item image URL for product items list (returns single URL)
     * 
     * @param string $productCode
     * @param string $isku
     * @return string|null Single image URL or null
     */
    public function getProductItemImageUrlForList(string $productCode, string $isku): ?string
    {
        return $this->getSingleProductItemImageUrl($productCode, $isku);
    }

    /**
     * Get product image URL for product items list (returns single URL)
     * 
     * @param string $productCode
     * @return string|null Single image URL or null
     */
    public function getProductImageUrlForList(string $productCode): ?string
    {
        return $this->getSingleProductImageUrl($productCode);
    }

    /**
     * Get product item image URL for entities by tag (returns single URL)
     * 
     * @param string $productCode
     * @param string $isku
     * @return string|null Single image URL or null
     */
    public function getProductItemImageUrlForTag(string $productCode, string $isku): ?string
    {
        return $this->getSingleProductItemImageUrl($productCode, $isku);
    }

    /**
     * Get product image URL for entities by tag (returns single URL)
     * 
     * @param string $productCode
     * @return string|null Single image URL or null
     */
    public function getProductImageUrlForTag(string $productCode): ?string
    {
        return $this->getSingleProductImageUrl($productCode);
    }

    /**
     * Get product item image URL for product items list (returns single URL)
     * 
     * @param string $productCode
     * @param string $isku
     * @return string|null Single image URL or null
     */
    public function getProductItemImageUrlForProductItems(string $productCode, string $isku): ?string
    {
        return $this->getSingleProductItemImageUrl($productCode, $isku);
    }

    /**
     * Upload images for a product
     */
    public function uploadProductImages(string $productCode, array $images): array
    {
        $this->validateProductExists($productCode);
        $this->validateImageFiles($images);

        $uploadedImages = [];

        foreach ($images as $index => $image) {
            $suffix = $index === 0 ? 'Main' : '_' . $index;
            $filename = $productCode . $suffix . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs($productCode, $filename, 'public');
            
            $uploadedImages[] = [
                'filename' => $filename,
                'url' => asset("storage/{$path}"),
                'path' => $path,
            ];
        }

        return $uploadedImages;
    }

    /**
     * Upload images for a product item
     */
    public function uploadProductItemImages(string $productItemCode, array $images): array
    {
        // Find the product code for this item
        $productCode = $this->getProductCodeForItem($productItemCode);
        if (!$productCode) {
            throw new \Exception("Product item {$productItemCode} not found");
        }

        $this->validateImageFiles($images);

        $uploadedImages = [];

        foreach ($images as $index => $image) {
            $suffix = $index === 0 ? 'Main' : '_' . $index;
            $filename = $productItemCode . $suffix . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs("{$productCode}/variant/{$productItemCode}", $filename, 'public');
            
            $uploadedImages[] = [
                'filename' => $filename,
                'url' => asset("storage/{$path}"),
                'path' => $path,
            ];
        }

        return $uploadedImages;
    }

    /**
     * Download and organize product images from Google Drive
     */
    public function downloadProductImages(string $driveUrl): array
    {
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

        // Process downloaded files (simplified version)
        $processedProducts = 0;
        $processedImages = 0;

        // Process each product directory
        $productDirs = glob("$tempDir/*", GLOB_ONLYDIR);
        foreach ($productDirs as $productDir) {
            $productCode = basename($productDir);

            // Create product directory in storage
            Storage::disk('public')->makeDirectory("products/$productCode");

            // Process product images
            $productFiles = glob("$productDir/*.jpg") + glob("$productDir/*.jpeg") + 
                           glob("$productDir/*.png") + glob("$productDir/*.gif");
            
            foreach ($productFiles as $file) {
                $filename = basename($file);
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                // Rename file to match expected pattern if needed
                if (!preg_match('/^' . preg_quote($productCode, '/') . '(_Main|_\\d+)?\.' . $extension . '$/', $filename)) {
                    if (strpos($filename, '_Main') !== false) {
                        $newFilename = $productCode . '_Main.' . $extension;
                    } else {
                        $newFilename = $productCode . '_' . $processedImages . '.' . $extension;
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

            $processedProducts++;
        }

        // Clean up temporary directory
        shell_exec("rm -rf \"$tempDir\"");

        return [
            'products_processed' => $processedProducts,
            'images_processed' => $processedImages,
            'drive_url' => $driveUrl
        ];
    }

    /**
     * Get product code for a product item
     */
    private function getProductCodeForItem(string $productItemCode): ?string
    {
        return \DB::table('product_item')
            ->where('product_item_code', $productItemCode)
            ->value('product_code');
    }

    /**
     * Validate that product exists
     */
    private function validateProductExists(string $productCode): void
    {
        if (!Product::where('product_code', $productCode)->exists()) {
            throw new \Exception("Product {$productCode} not found");
        }
    }

    /**
     * Validate image files
     */
    private function validateImageFiles(array $images): void
    {
        foreach ($images as $image) {
            if (!$image->isValid()) {
                throw new \Exception("Invalid image file provided");
            }
        }
    }
}
