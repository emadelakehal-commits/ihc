<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductRelationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductRelationController extends Controller
{
    public function __construct(private ProductRelationService $relationService) {}

    /**
     * Get related products for a given product or product item
     */
    public function getRelatedProducts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'entity_code' => 'required|string',
                'lang' => 'required|string|size:2|exists:lkp_language,code'
            ]);

            $entityCode = $request->input('entity_code');
            $language = $request->input('lang');

            $relatedProducts = $this->relationService->getRelatedProducts($entityCode, $language);

            return response()->json([
                'success' => true,
                'data' => $relatedProducts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting related products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get parent product code for a given ISKU
     */
    public function getProductCodeByIsku(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'isku' => 'required|string|exists:product_item,isku'
            ]);

            $isku = $request->input('isku');

            $productCode = $this->relationService->getProductCodeByIsku($isku);

            return response()->json([
                'success' => true,
                'data' => [
                    'isku' => $isku,
                    'product_code' => $productCode
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting product code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a related product relationship
     */
    public function createRelatedProduct(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from_entity' => 'required|string',
                'to_entity' => 'required|string',
                'relation_type' => 'sometimes|string|default:related'
            ]);

            $fromEntity = $request->input('from_entity');
            $toEntity = $request->input('to_entity');
            $relationType = $request->input('relation_type', 'related');

            $success = $this->relationService->createRelatedProduct($fromEntity, $toEntity, $relationType);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Related product created successfully'
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create related product'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating related product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a related product relationship
     */
    public function removeRelatedProduct(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from_entity' => 'required|string',
                'to_entity' => 'required|string'
            ]);

            $fromEntity = $request->input('from_entity');
            $toEntity = $request->input('to_entity');

            $success = $this->relationService->removeRelatedProduct($fromEntity, $toEntity);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Related product removed successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove related product'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing related product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}