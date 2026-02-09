<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductItemTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductTagService
{
    /**
     * Get entities (products and product items) by tag code.
     */
    public function getEntitiesByTag(string $tagCode, string $language): array
    {
        try {
            // Validate language exists
            if (!DB::table('lkp_language')->where('code', $language)->exists()) {
                throw new \Exception('Invalid language code');
            }

            $entities = [];

            // First, check if tag_code exists in lkp_tag (for products)
            $tag = DB::table('lkp_tag')
                ->where('tag_code', $tagCode)
                ->first();

            if ($tag) {
                $entities = array_merge($entities, $this->getProductsByTag($tagCode, $language));
            }

            // Second, check if tag_code exists in lkp_item_tag (for product items)
            $itemTag = DB::table('lkp_item_tag')
                ->where('item_tag_code', $tagCode)
                ->first();

            if ($itemTag) {
                $entities = array_merge($entities, $this->getProductItemsByTag($tagCode, $language));
            }

            // Get the tag name based on language
            $tagName = $this->getTagName($tagCode, $language);

            return [
                'tag_name' => $tagName,
                'data' => $entities
            ];

        } catch (\Exception $e) {
            Log::error('Error getting entities by tag', [
                'tagCode' => $tagCode,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error getting entities by tag: ' . $e->getMessage());
        }
    }

    /**
     * Get products associated with a tag.
     */
    private function getProductsByTag(string $tagCode, string $language): array
    {
        $productCodes = DB::table('product_tag')
            ->where('tag_code', $tagCode)
            ->pluck('product_code')
            ->toArray();

        $entities = [];
        foreach ($productCodes as $productCode) {
            $product = Product::with(['translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }])->where('product_code', $productCode)->first();

            if ($product) {
                // Get product image using ImageService (placeholder)
                $imageUrl = $this->getProductImageUrlForCategories($product->product_code);

                $entities[] = [
                    'type' => 'product',
                    'product_code' => $product->product_code,
                    'image_url' => $imageUrl,
                    'cost' => null,
                    'cost_currency' => null,
                    'rrp' => null,
                    'rrp_currency' => null,
                    'title' => $product->translations->first()?->title ?? $product->product_code,
                ];
            }
        }

        return $entities;
    }

    /**
     * Get product items associated with a tag.
     */
    private function getProductItemsByTag(string $tagCode, string $language): array
    {
        $iskus = DB::table('product_item_tag')
            ->where('item_tag_code', $tagCode)
            ->pluck('isku')
            ->toArray();

        $entities = [];
        foreach ($iskus as $isku) {
            $productItem = ProductItem::with(['translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }])->where('isku', $isku)->first();

            if ($productItem) {
                // Get product item image using ImageService (placeholder)
                $imageUrl = $this->getProductItemImageUrlForCategories($productItem->product_code, $productItem->isku);

                $entities[] = [
                    'type' => 'product_item',
                    'product_code' => $productItem->product_code,
                    'isku' => $productItem->isku,
                    'image_url' => $imageUrl,
                    'cost' => $productItem->cost,
                    'cost_currency' => $productItem->cost_currency,
                    'rrp' => $productItem->rrp,
                    'rrp_currency' => $productItem->rrp_currency,
                    'title' => $productItem->translations->first()?->title ?? $productItem->product_item_code,
                ];
            }
        }

        return $entities;
    }

    /**
     * Get tag name based on language.
     */
    private function getTagName(string $tagCode, string $language): ?string
    {
        // First check if it's a product tag
        $tagTranslation = DB::table('lkp_tag_translation')
            ->where('tag_code', $tagCode)
            ->where('language', $language)
            ->first();

        if ($tagTranslation) {
            return $tagTranslation->name;
        }

        // Then check if it's an item tag
        $itemTagTranslation = DB::table('lkp_item_tag_translation')
            ->where('item_tag_code', $tagCode)
            ->where('language', $language)
            ->first();

        if ($itemTagTranslation) {
            return $itemTagTranslation->name;
        }

        // If no translation found, return the tag code
        return $tagCode;
    }

    /**
     * Get product image URL for categories (placeholder).
     */
    private function getProductImageUrlForCategories(string $productCode): ?string
    {
        // This should be implemented to use the actual ImageService
        // For now, returning null as placeholder
        return null;
    }

    /**
     * Get product item image URL for categories (placeholder).
     */
    private function getProductItemImageUrlForCategories(string $productCode, string $isku): ?string
    {
        // This should be implemented to use the actual ImageService
        // For now, returning null as placeholder
        return null;
    }
}