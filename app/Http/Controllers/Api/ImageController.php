<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImagesRequest;
use App\Http\Requests\UploadZipRequest;
use App\Services\ImageDownloadService;
use App\Services\ZipExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function __construct(
        private ImageDownloadService $imageDownloadService,
        private ZipExtractionService $zipExtractionService
    ) {}

    /**
     * Upload images for product
     */
    public function uploadProductImages(UploadImagesRequest $request, string $productCode): JsonResponse
    {
        try {
            // Check if product exists
            $product = \App\Models\Product::where('product_code', $productCode)->first();
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $uploadedImages = [];

            foreach ($request->file('images') as $index => $image) {
                // New naming convention: productcode_main for first image, productcode_1, productcode_2, etc. for others
                $suffix = $index === 0 ? '_main' : '_' . $index;
                $filename = $productCode . $suffix . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs($productCode, $filename, 'public');
                $uploadedImages[] = [
                    'filename' => $filename,
                    'url' => asset("storage/{$path}"),
                    'path' => $path,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $uploadedImages
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload images for product item
     */
    public function uploadProductItemImages(UploadImagesRequest $request, string $productItemCode): JsonResponse
    {
        try {
            // Check if product item exists
            $productItem = \App\Models\ProductItem::where('product_item_code', $productItemCode)->first();
            if (!$productItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product item not found'
                ], 404);
            }

            $uploadedImages = [];

            foreach ($request->file('images') as $index => $image) {
                // New naming convention: isku_main for first image, isku_1, isku_2, etc. for others
                $suffix = $index === 0 ? '_main' : '_' . $index;
                $filename = $productItemCode . $suffix . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs("{$productItem->product_code}/variant/{$productItemCode}", $filename, 'public');
                $uploadedImages[] = [
                    'filename' => $filename,
                    'url' => asset("storage/{$path}"),
                    'path' => $path,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $uploadedImages
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload and extract zip file containing product images
     */
    public function uploadZipFile(UploadZipRequest $request): JsonResponse
    {
        try {
            $zipFile = $request->file('zip_file');
            
            // Extract and process the zip file
            $result = $this->zipExtractionService->extractZipFile($zipFile);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process zip file',
                    'error' => $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Zip file processed successfully',
                'data' => $result['data']
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload zip file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product images
     */
    public function getProductImages(string $productCode): JsonResponse
    {
        try {
            $images = $this->imageDownloadService->getProductImages($productCode);

            return response()->json([
                'success' => true,
                'data' => $images
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get product images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product item images
     */
    public function getProductItemImages(string $productCode, string $isku): JsonResponse
    {
        try {
            $images = $this->imageDownloadService->getProductItemImages($productCode, $isku);

            return response()->json([
                'success' => true,
                'data' => $images
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get product item images',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}