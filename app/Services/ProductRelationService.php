<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductRelationService
{
    /**
     * Get related products for a given product or product item
     */
    public function getRelatedProducts(string $entityCode, string $language): array
    {
        // Determine entity type by checking if it exists as product_code or isku
        $entityType = null;
        $entity = null;

        if (Product::where('product_code', $entityCode)->exists()) {
            $entityType = 'product';
            $entity = Product::with(['translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }])->where('product_code', $entityCode)->first();
        } elseif (ProductItem::where('isku', $entityCode)->exists()) {
            $entityType = 'product_item';
            $entity = ProductItem::with(['translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }])->where('isku', $entityCode)->first();
        } else {
            throw new \Exception("Entity {$entityCode} not found");
        }

        // Get related products/items (bidirectional)
        $relatedProducts = [];

        // Get relationships where entity is the "from" entity
        $fromRelations = DB::table('product_related')
            ->where('relation_type', 'related')
            ->where('from_entity_type', $entityType)
            ->where('from_entity_code', $entityCode)
            ->select('to_entity_type', 'to_entity_code')
            ->get();

        // Get relationships where entity is the "to" entity
        $toRelations = DB::table('product_related')
            ->where('relation_type', 'related')
            ->where('to_entity_type', $entityType)
            ->where('to_entity_code', $entityCode)
            ->select('from_entity_type as to_entity_type', 'from_entity_code as to_entity_code')
            ->get();

        // Combine and deduplicate
        $allRelations = $fromRelations->merge($toRelations);
        $seen = [];
        foreach ($allRelations as $relation) {
            $key = $relation->to_entity_type . '_' . $relation->to_entity_code;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $relatedProducts[] = [
                    'entity_type' => $relation->to_entity_type,
                    'entity_code' => $relation->to_entity_code,
                ];
            }
        }

        // Build response array with required fields
        $response = [];
        foreach ($relatedProducts as $related) {
            $relatedData = null;

            if ($related['entity_type'] === 'product') {
                $product = Product::with(['translations' => function ($query) use ($language) {
                    $query->where('language', $language);
                }])->where('product_code', $related['entity_code'])->first();

                if ($product) {
                    // Get product image using ImageService
                    $imageUrl = $this->getProductImageUrlForRelated($product->product_code);

                    $relatedData = [
                        'type' => 'product',
                        'code' => $product->product_code,
                        'image_url' => $imageUrl,
                        'cost' => null, // Products don't have direct cost
                        'cost_currency' => null,
                        'rrp' => null, // Products don't have direct RRP
                        'rrp_currency' => null,
                        'title' => $product->translations->first()?->title ?? $product->product_code,
                    ];
                }
            } elseif ($related['entity_type'] === 'product_item') {
                $productItem = ProductItem::with(['translations' => function ($query) use ($language) {
                    $query->where('language', $language);
                }])->where('isku', $related['entity_code'])->first();

                if ($productItem) {
                    // Get product item image using ImageService
                    $imageUrl = $this->getProductItemImageUrlForRelated($productItem->product_code, $productItem->isku);

                    $relatedData = [
                        'type' => 'product_item',
                        'code' => $productItem->isku,
                        'isku' => $productItem->isku,
                        'product_code' => $productItem->product_code,
                        'image_url' => $imageUrl,
                        'cost' => $productItem->cost,
                        'cost_currency' => $productItem->cost_currency,
                        'rrp' => $productItem->rrp,
                        'rrp_currency' => $productItem->rrp_currency,
                        'title' => $productItem->translations->first()?->title ?? $productItem->product_item_code,
                    ];
                }
            }

            if ($relatedData) {
                $response[] = $relatedData;
            }
        }

        return $response;
    }

    /**
     * Get parent product code for a given ISKU
     */
    public function getProductCodeByIsku(string $isku): string
    {
        $productItem = DB::table('product_item')
            ->where('isku', $isku)
            ->select('product_code')
            ->first();

        if (!$productItem) {
            throw new \Exception("Product item with ISKU {$isku} not found");
        }

        return $productItem->product_code;
    }

    /**
     * Create a related product relationship
     */
    public function createRelatedProduct(string $fromEntity, string $toEntity, string $relationType = 'related'): bool
    {
        try {
            // Check if relationship already exists
            $existing = DB::table('product_related')
                ->where('from_entity_type', $this->getEntityType($fromEntity))
                ->where('from_entity_code', $fromEntity)
                ->where('to_entity_type', $this->getEntityType($toEntity))
                ->where('to_entity_code', $toEntity)
                ->where('relation_type', $relationType)
                ->exists();

            if ($existing) {
                return true; // Relationship already exists
            }

            // Create the relationship
            DB::table('product_related')->insert([
                'from_entity_type' => $this->getEntityType($fromEntity),
                'from_entity_code' => $fromEntity,
                'to_entity_type' => $this->getEntityType($toEntity),
                'to_entity_code' => $toEntity,
                'relation_type' => $relationType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create bidirectional relationship
            DB::table('product_related')->insert([
                'from_entity_type' => $this->getEntityType($toEntity),
                'from_entity_code' => $toEntity,
                'to_entity_type' => $this->getEntityType($fromEntity),
                'to_entity_code' => $fromEntity,
                'relation_type' => $relationType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create related product', [
                'from_entity' => $fromEntity,
                'to_entity' => $toEntity,
                'relation_type' => $relationType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove a related product relationship
     */
    public function removeRelatedProduct(string $fromEntity, string $toEntity): bool
    {
        try {
            // Remove both directions of the relationship
            DB::table('product_related')
                ->where(function ($query) use ($fromEntity, $toEntity) {
                    $query->where('from_entity_type', $this->getEntityType($fromEntity))
                          ->where('from_entity_code', $fromEntity)
                          ->where('to_entity_type', $this->getEntityType($toEntity))
                          ->where('to_entity_code', $toEntity);
                })
                ->orWhere(function ($query) use ($fromEntity, $toEntity) {
                    $query->where('from_entity_type', $this->getEntityType($toEntity))
                          ->where('from_entity_code', $toEntity)
                          ->where('to_entity_type', $this->getEntityType($fromEntity))
                          ->where('to_entity_code', $fromEntity);
                })
                ->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove related product', [
                'from_entity' => $fromEntity,
                'to_entity' => $toEntity,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get entity type (product or product_item) based on code
     */
    private function getEntityType(string $entityCode): string
    {
        if (Product::where('product_code', $entityCode)->exists()) {
            return 'product';
        } elseif (ProductItem::where('isku', $entityCode)->exists()) {
            return 'product_item';
        }
        
        throw new \Exception("Entity {$entityCode} not found");
    }

    /**
     * Get product image URL for related products (returns single URL)
     */
    private function getProductImageUrlForRelated(string $productCode): ?string
    {
        // This would use the ImageService, but we need to inject it
        // For now, return a placeholder
        // In a real implementation, you would inject ImageService and call:
        // return $this->imageService->getSingleProductImageUrl($productCode);
        return null;
    }

    /**
     * Get product item image URL for related products (returns single URL)
     */
    private function getProductItemImageUrlForRelated(string $productCode, string $isku): ?string
    {
        // This would use the ImageService, but we need to inject it
        // For now, return a placeholder
        // In a real implementation, you would inject ImageService and call:
        // return $this->imageService->getSingleProductItemImageUrl($productCode, $isku);
        return null;
    }
}