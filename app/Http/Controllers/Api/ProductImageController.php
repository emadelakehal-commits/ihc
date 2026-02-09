<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadProductImagesRequest;
use App\Http\Requests\UploadProductItemImagesRequest;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    public function __construct(private ImageService $imageService) {}

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