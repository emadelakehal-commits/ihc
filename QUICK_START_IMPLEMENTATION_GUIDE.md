# Quick Start Implementation Guide

## Week 1: Service Layer Extraction - Your First Steps

This guide will help you start refactoring your ProductController immediately with minimal risk.

## Step 1: Enhance the Existing ImageService (Start Here!)

You already have an `ImageService` that handles **retrieving** images. Now we'll add **upload** functionality to it.

### Update app/Services/ImageService.php
Add these new methods to your existing ImageService:

```php
<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageService
{
    // ... existing methods (getProductImages, getProductItemImages, etc.) ...

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
```

## Step 2: Create Request Validation Classes

### Create app/Http/Requests/UploadProductImagesRequest.php
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductImagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'At least one image is required',
            'images.*.image' => 'Each file must be an image',
            'images.*.mimes' => 'Images must be jpeg, png, jpg, or gif format',
            'images.*.max' => 'Each image must not exceed 2MB',
        ];
    }
}
```

### Create app/Http/Requests/UploadProductItemImagesRequest.php
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductItemImagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'At least one image is required',
            'images.*.image' => 'Each file must be an image',
            'images.*.mimes' => 'Images must be jpeg, png, jpg, or gif format',
            'images.*.max' => 'Each image must not exceed 2MB',
        ];
    }
}
```

## Step 3: Create New Specialized Controllers

### Create app/Http/Controllers/Api/ProductImageController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadProductImagesRequest;
use App\Http\Requests\UploadProductItemImagesRequest;
use App\Services\ProductImageService;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    public function __construct(private ProductImageService $imageService) {}

    /**
     * Upload images for a product
     */
    public function uploadProductImages(UploadProductImagesRequest $request, string $productCode): JsonResponse
    {
        try {
            $images = $this->imageService->uploadProductImages($productCode, $request->file('images'));
            
            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $images
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Upload images for a product item
     */
    public function uploadProductItemImages(UploadProductItemImagesRequest $request, string $productItemCode): JsonResponse
    {
        try {
            $images = $this->imageService->uploadProductItemImages($productItemCode, $request->file('images'));
            
            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $images
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Download and organize product images from Google Drive
     */
    public function downloadProductImages(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'drive_url' => 'required|url'
            ]);

            $result = $this->imageService->downloadProductImages($request->input('drive_url'));
            
            return response()->json([
                'success' => true,
                'message' => 'Images downloaded and organized successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download images',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

## Step 4: Update Routes (NO CHANGES TO EXISTING ENDPOINTS!)

### Current API Endpoints Analysis
Looking at your `routes/api.php`, you already have these image endpoints:

**✅ EXISTING ENDPOINTS (NO CHANGES):**
- `POST /api/products/{productCode}/images` → `ImageController@uploadProductImages`
- `POST /api/products/{productItemCode}/item-images` → `ImageController@uploadProductItemImages`

**✅ NEW ENDPOINT (OPTIONAL):**
- `POST /api/products/download-images` → `ProductImageController@downloadProductImages`

### What This Means:
- **Your existing frontend calls will work exactly the same**
- **No breaking changes to any API consumers**
- **Only the internal implementation changes (ProductController → ImageService)**

### Routes Update (Only if you want the new download endpoint):
```php
// Add ONLY if you want the download functionality
Route::post('products/download-images', [ProductImageController::class, 'downloadProductImages']);
```

**If you don't want any new endpoints, skip this step entirely!**

## Step 5: Update Your Existing ProductController

### Modify the existing image upload methods in ProductController
Replace these methods in your current ProductController:

```php
// Replace uploadProductImages method
public function uploadProductImages(Request $request, string $productCode)
{
    try {
        $images = $this->imageService->uploadProductImages($productCode, $request->file('images'));
        
        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $images
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

// Replace uploadProductItemImages method
public function uploadProductItemImages(Request $request, string $productItemCode)
{
    try {
        $images = $this->imageService->uploadProductItemImages($productItemCode, $request->file('images'));
        
        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $images
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}
```

## Step 6: Test Your Changes

### Test the new service
Create a simple test to verify your service works:
```php
// In a test file or tinker
use App\Services\ProductImageService;

$service = new ProductImageService();
// Test with a valid product code and mock images
```

### Test the new controller
Use Postman or your frontend to test:
- POST `/api/products/{productCode}/images` with image files
- POST `/api/products/{productItemCode}/item-images` with image files

## What You've Accomplished

✅ **Extracted image-related logic** from 100+ lines in ProductController to a focused service
✅ **Created reusable service** that can be used in multiple controllers
✅ **Added proper validation** with custom request classes
✅ **Maintained backward compatibility** - existing API endpoints still work
✅ **Improved testability** - service can be unit tested independently

## Next Steps for Week 2

Once you're comfortable with the image service, you can apply the same pattern to:

1. **Excel Import Logic** - Extract `processExcel()` method to `ProductImportService`
2. **Related Products Logic** - Extract `getRelatedProducts()` to `ProductRelationService`
3. **Product Update Logic** - Extract complex update logic to `ProductUpdateService`

## Quick Wins You'll See Immediately

- **Reduced controller complexity** - ProductController is now smaller and more focused
- **Better error handling** - Centralized validation and error messages
- **Improved code organization** - Related functionality grouped together
- **Easier testing** - Each service can be tested independently
- **Better maintainability** - Changes to image logic don't affect other controller methods

## Troubleshooting

**If you get dependency injection errors:**
Make sure you've added the service to your ProductController constructor:
```php
public function __construct(
    private ProductService $productService,
    private ProductImageService $imageService  // Add this line
) {}
```

**If validation fails:**
Check that your request classes are in the correct namespace and extend FormRequest.

**If images don't upload:**
Verify your storage configuration and that the public disk is properly configured.

This gives you a solid foundation to build on. Once you're comfortable with this pattern, you can apply it to other parts of your controller systematically!