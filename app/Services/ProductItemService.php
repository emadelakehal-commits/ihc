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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductItemService
{
    /**
     * Store product items under a product.
     */
    public function storeProductItems(string $productCode, array $productItemsData): array
    {
        // Check if product exists
        $product = Product::where('product_code', $productCode)->first();
        if (!$product) {
            throw new \Exception('Product not found');
        }

        $validator = Validator::make([
            'productItems' => $productItemsData
        ], [
            'productItems' => 'required|array|min:1',
            'productItems.*.productItemCode' => 'required|string|max:100',
            'productItems.*.isActive' => 'required|boolean',
            'productItems.*.cost' => 'nullable|numeric|min:0',
            'productItems.*.costCurrency' => 'nullable|string|size:3|exists:lkp_currency,code',
            'productItems.*.rrp' => 'nullable|numeric|min:0',
            'productItems.*.rrpCurrency' => 'nullable|string|size:3|exists:lkp_currency,code',
            'productItems.*.categories' => 'array',
            'productItems.*.categories.*' => 'string|exists:lkp_category,category_code',
            'productItems.*.attributes' => 'array',
            'productItems.*.attributes.*' => 'string',
            'productItems.*.deliveries' => 'array',
            'productItems.*.deliveries.*.min' => 'required|integer|min:0',
            'productItems.*.deliveries.*.max' => 'required|integer|min:0|gte:productItems.*.deliveries.*.min',
            'productItems.*.documents' => 'array',
            'productItems.*.documents.*.type' => 'required|string|in:manual,technical,warranty',
            'productItems.*.documents.*.url' => 'required|string|max:500',
            'productItems.*.isku' => 'required|string|max:100|unique:product_item,isku',
            'productItems.*.translations' => 'required|array|min:1',
            'productItems.*.translations.*.language' => 'required|string|max:10|exists:lkp_language,code',
            'productItems.*.translations.*.title' => 'required|string|max:255',
            'productItems.*.translations.*.short_desc' => 'nullable|string',
            'productItems.*.tags' => 'array',
            'productItems.*.tags.*' => 'string|exists:lkp_tag,tag_code',
            'productItems.*.itemTags' => 'array',
            'productItems.*.itemTags.*' => 'string|exists:lkp_item_tag,item_tag_code',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        try {
            DB::beginTransaction();

            $createdProductItems = [];

            foreach ($productItemsData as $productItemData) {
                $productItem = ProductItem::create([
                    'product_item_code' => $productItemData['productItemCode'],
                    'isku' => $productItemData['isku'],
                    'product_code' => $productCode,
                    'is_active' => $productItemData['isActive'],
                    'cost' => $productItemData['cost'] ?? null,
                    'cost_currency' => $productItemData['costCurrency'] ?? null,
                    'rrp' => $productItemData['rrp'] ?? null,
                    'rrp_currency' => $productItemData['rrpCurrency'] ?? null,
                ]);

                // Product Item Translations
                if (!empty($productItemData['translations'])) {
                    foreach ($productItemData['translations'] as $translationData) {
                        ProductItemTranslation::create([
                            'isku' => $productItem->isku,
                            'language' => $translationData['language'],
                            'title' => $translationData['title'],
                            'short_desc' => $translationData['short_desc'] ?? null,
                        ]);
                    }
                }

                // Create product item image directory - new structure: product_code/variant/isku/
                Storage::disk('public')->makeDirectory("{$productCode}/variant/{$productItemData['isku']}");

                // Categories
                if (!empty($productItemData['categories'])) {
                    foreach ($productItemData['categories'] as $categoryCode) {
                        DB::table('product_category')->insertOrIgnore([
                            'product_code' => $productItem->isku,
                            'category_code' => $categoryCode,
                        ]);
                    }
                }

                // Attributes - For new products, just create them (no existing check needed)
                if (!empty($productItemData['attributes'])) {
                    // Check if it's the grouped format (object with language keys)
                    if (isset($productItemData['attributes']['en']) || isset($productItemData['attributes']['de'])) {
                        // Grouped format: {"en": [{"name": "length", "value": "600"}], "de": [...]}
                        foreach ($productItemData['attributes'] as $language => $attributes) {
                            foreach ($attributes as $attribute) {
                                DB::table('product_attribute_value')->insert([
                                    'isku' => $productItem->isku,
                                    'attribute_name' => $attribute['name'],
                                    'language' => $language,
                                    'value' => $attribute['value'],
                                ]);
                            }
                        }
                    } elseif (is_array($productItemData['attributes']) && isset($productItemData['attributes'][0]) && is_array($productItemData['attributes'][0])) {
                        // Array format: [{"name": "length", "language": "en", "value": "600"}]
                        foreach ($productItemData['attributes'] as $attribute) {
                            DB::table('product_attribute_value')->insert([
                                'isku' => $productItem->isku,
                                'attribute_name' => $attribute['name'],
                                'language' => $attribute['language'] ?? 'en',
                                'value' => $attribute['value'],
                            ]);
                        }
                    } else {
                        // Old format: {"length": "600"} - assume 'en' language
                        foreach ($productItemData['attributes'] as $attributeName => $value) {
                            DB::table('product_attribute_value')->insert([
                                'isku' => $productItem->isku,
                                'attribute_name' => $attributeName,
                                'language' => 'en',
                                'value' => $value,
                            ]);
                        }
                    }
                }

                // Deliveries
                if (!empty($productItemData['deliveries'])) {
                    foreach ($productItemData['deliveries'] as $domainCode => $delivery) {
                        DB::table('product_delivery')->insert([
                            'isku' => $productItem->isku,
                            'domain_id' => $domainCode,
                            'delivery_min' => $delivery['min'],
                            'delivery_max' => $delivery['max'],
                        ]);
                    }
                }

                // Documents
                if (!empty($productItemData['documents'])) {
                    foreach ($productItemData['documents'] as $document) {
                        DB::table('product_document')->insert([
                            'product_code' => $productItem->isku,
                            'doc_type' => $document['type'],
                            'file_url' => $document['url'],
                        ]);
                    }
                }

                // Tags (for main product)
                if (!empty($productItemData['tags'])) {
                    foreach ($productItemData['tags'] as $tagCode) {
                        DB::table('product_tag')->insertOrIgnore([
                            'product_code' => $productCode,
                            'tag_code' => $tagCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Item Tags
                if (!empty($productItemData['itemTags'])) {
                    foreach ($productItemData['itemTags'] as $itemTagCode) {
                        DB::table('product_item_tag')->insert([
                            'isku' => $productItem->isku,
                            'item_tag_code' => $itemTagCode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $createdProductItems[] = $productItem->load(['categories', 'attributeValues', 'deliveries', 'documents', 'tags', 'itemTags']);
            }

            DB::commit();

            return $createdProductItems;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create product items: ' . $e->getMessage());
        }
    }

    /**
     * Get product items for a specific product code with pagination.
     */
    public function getProductItems(string $productCode, string $language, int $page = 1, int $perPage = 20): array
    {
        // Check if product exists
        $product = Product::where('product_code', $productCode)->first();
        if (!$product) {
            throw new \Exception('Product not found');
        }

        $query = ProductItem::with([
            'translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }
        ])->where('product_code', $productCode);

        // Apply pagination
        $paginatedItems = $query->paginate($perPage, ['*'], 'page', $page);

        $response = [];
        foreach ($paginatedItems->items() as $productItem) {
            $response[] = [
                'item_code' => $productItem->product_item_code,
                'isku' => $productItem->isku,
                'title' => $productItem->translations->first()?->title ?? $productItem->product_item_code,
                'cost' => $productItem->cost,
                'rrp' => $productItem->rrp,
                'cost_currency' => $productItem->cost_currency,
                'rrp_currency' => $productItem->rrp_currency,
                'is_active' => $productItem->is_active,
                'created_at' => $productItem->created_at,
                'updated_at' => $productItem->updated_at,
            ];
        }

        return [
            'data' => $response,
            'pagination' => [
                'current_page' => $paginatedItems->currentPage(),
                'per_page' => $paginatedItems->perPage(),
                'total' => $paginatedItems->total(),
                'last_page' => $paginatedItems->lastPage(),
                'from' => $paginatedItems->firstItem(),
                'to' => $paginatedItems->lastItem(),
                'has_more_pages' => $paginatedItems->hasMorePages(),
                'prev_page_url' => $paginatedItems->previousPageUrl(),
                'next_page_url' => $paginatedItems->nextPageUrl(),
            ]
        ];
    }

    /**
     * Get product items for a specific product code without pagination.
     */
    public function getAllProductItems(string $productCode, string $language): array
    {
        // Check if product exists
        $product = Product::where('product_code', $productCode)->first();
        if (!$product) {
            throw new \Exception('Product not found');
        }

        $productItems = ProductItem::with([
            'translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }
        ])->where('product_code', $productCode)->get();

        $response = [];
        foreach ($productItems as $productItem) {
            $response[] = [
                'item_code' => $productItem->product_item_code,
                'isku' => $productItem->isku,
                'title' => $productItem->translations->first()?->title ?? $productItem->product_item_code,
                'cost' => $productItem->cost,
                'rrp' => $productItem->rrp,
                'cost_currency' => $productItem->cost_currency,
                'rrp_currency' => $productItem->rrp_currency,
                'is_active' => $productItem->is_active,
                'created_at' => $productItem->created_at,
                'updated_at' => $productItem->updated_at,
            ];
        }

        return $response;
    }
}