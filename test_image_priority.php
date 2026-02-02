<?php

// Simple test script to verify the ImageService priority fix
require_once 'vendor/autoload.php';

use App\Services\ImageService;

// Create ImageService instance
$imageService = new ImageService();

// Test product BDB
$productCode = 'BDB';
$isku = 'BDB'; // For product items, isku is usually the same as product code

echo "Testing ImageService priority fix for product BDB:\n\n";

// Test getProductImages method
echo "=== Testing getProductImages ===\n";
$productImages = $imageService->getProductImages($productCode);
echo "Product images found: " . count($productImages) . "\n";
foreach ($productImages as $index => $imageUrl) {
    echo "Image " . ($index + 1) . ": " . basename(parse_url($imageUrl, PHP_URL_PATH)) . "\n";
}

// Test getSingleProductImageUrl method
echo "\n=== Testing getSingleProductImageUrl ===\n";
$singleImageUrl = $imageService->getSingleProductImageUrl($productCode);
if ($singleImageUrl) {
    $imageName = basename(parse_url($singleImageUrl, PHP_URL_PATH));
    echo "Single product image: " . $imageName . "\n";
    
    if (strpos($imageName, '_Main') !== false) {
        echo "✅ SUCCESS: _Main image is prioritized!\n";
    } else {
        echo "❌ ISSUE: _Main image is not prioritized, got: " . $imageName . "\n";
    }
} else {
    echo "No product image found\n";
}

echo "\nTest completed.\n";