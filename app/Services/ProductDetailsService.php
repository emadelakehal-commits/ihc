<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\ProductDelivery;
use App\Models\ProductDocument;
use App\Models\ProductItemTranslation;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ImageService;

class ProductDetailsService
{
    /**
     * @var ImageService
     */
    private $imageService;

    /**
     * ProductDetailsService constructor.
     */
    public function __construct()
    {
        $this->imageService = app(ImageService::class);
    }

    /**
     * Display the product with its product items and all related data.
     */
    public function getProductDetails(string $productCode, string $language = 'en'): array
    {
        try {
            $product = Product::with([
                'translations' => function ($query) use ($language) {
                    $query->where('language', $language);
                },
                'tags'
            ])->where('product_code', $productCode)->first();

            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Load product items with their relationships separately
            $productItems = ProductItem::with([
                'categories',
                'attributeValues',
                'deliveries',
                'documents',
                'translations' => function ($query) use ($language) {
                    $query->where('language', $language);
                },
                'itemTags.translations' => function ($query) use ($language) {
                    $query->where('language', $language);
                }
            ])->where('product_code', $productCode)->get();

            // Load product categories
            $productCategories = DB::table('product_category')
                ->where('product_code', $productCode)
                ->pluck('category_code')
                ->toArray();

            // Load related products for the main product (bidirectional) - optimized single query
            $productRelatedProducts = $this->getRelatedProductsForEntity($productCode, 'product');

            // Get product images using ImageService
            $productImages = $this->getProductImages($productCode);

            // Normalize the response to remove redundant fields
            $normalizedData = [
                'product_code' => $product->product_code,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'images' => $productImages,
                'categories' => $productCategories,
                'related_products' => $productRelatedProducts,
                'translations' => $product->translations->map(function ($translation) {
                    return [
                        'language' => $translation->language,
                        'title' => $translation->title,
                        'summary' => $translation->summary,
                        'description' => $translation->description,
                    ];
                }),
                'tags' => $this->getProductTags($product, $language),
                'product_items' => $this->formatProductItems($productItems, $productCode, $language),
            ];

            return $normalizedData;

        } catch (\Exception $e) {
            Log::error('Error getting product details', [
                'productCode' => $productCode,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error getting product details: ' . $e->getMessage());
        }
    }

    /**
     * Get related products for an entity (product or product item) - bidirectional.
     */
    private function getRelatedProductsForEntity(string $entityCode, string $entityType): array
    {
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

        return $relatedProducts;
    }

    /**
     * Get product tags with translations.
     */
    private function getProductTags($product, string $language): array
    {
        if (!$product->tags) {
            return [];
        }

        return $product->tags->map(function ($tag) use ($language) {
            $tagTranslation = DB::table('lkp_tag_translation')
                ->where('tag_code', $tag->tag_code)
                ->where('language', $language)
                ->first();
            $tagName = $tagTranslation ? $tagTranslation->name : $tag->tag_code;
            return [
                'tag_code' => $tag->tag_code,
                'tag_name' => $tagName,
            ];
        })->toArray();
    }

    /**
     * Format product items with all their relationships.
     */
    private function formatProductItems($productItems, string $productCode, string $language): array
    {
        return $productItems->map(function ($productItem) use ($productCode, $language) {
            // Get product item images using ImageService
            $productItemImages = $this->getProductItemImages($productCode, $productItem->isku);

            // Load related products for this product item (bidirectional) - optimized single query
            $itemRelatedProducts = $this->getRelatedProductsForEntity($productItem->isku, 'product_item');

            return [
                'product_item_code' => $productItem->product_item_code,
                'isku' => $productItem->isku,
                'is_active' => $productItem->is_active,
                'created_at' => $productItem->created_at,
                'updated_at' => $productItem->updated_at,
                'cost' => $productItem->cost,
                'cost_currency' => $productItem->cost_currency,
                'rrp' => $productItem->rrp,
                'rrp_currency' => $productItem->rrp_currency,
                'availability' => $productItem->availability,
                'title' => $productItem->translations->where('language', $language)->first()?->title ?? $productItem->product_item_code,
                'variation_text' => $productItem->translations->where('language', $language)->first()?->variation_text,
                'images' => $productItemImages,
                'related_products' => $itemRelatedProducts,
                'categories' => $productItem->categories->pluck('category_code')->toArray(),
                'attributes' => $productItem->attributeValues->where('language', $language)->map(function ($attr) {
                    return [
                        'name' => $attr->attribute_name,
                        'value' => $attr->value,
                    ];
                })->toArray(),
                'deliveries' => $productItem->deliveries->map(function ($delivery) {
                    return [
                        'domain' => $delivery->domain_id,
                        'min' => $delivery->delivery_min,
                        'max' => $delivery->delivery_max,
                    ];
                })->toArray(),
                'documents' => $productItem->documents->map(function ($document) {
                    return [
                        'type' => $document->doc_type,
                        'url' => $document->file_url,
                    ];
                })->toArray(),
                'tags' => $productItem->itemTags->map(function ($itemTag) use ($language) {
                    $tagName = $itemTag->translations->where('language', $language)->first()?->name ?? $itemTag->item_tag_code;
                    return [
                        'tag_code' => $itemTag->item_tag_code,
                        'tag_name' => $tagName,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get product images using ImageService.
     */
    private function getProductImages(string $productCode): array
    {
        return $this->imageService->getProductImages($productCode);
    }

    /**
     * Get product item images using ImageService.
     */
    private function getProductItemImages(string $productCode, string $isku): array
    {
        return $this->imageService->getProductItemImages($productCode, $isku);
    }
}