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
}
