<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\GetProductDocumentsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\ProductDelivery;
use App\Models\ProductDocument;
use App\Models\ProductItemTranslation;
use App\Models\Language;
use App\Services\ProductService;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private \App\Services\ImageService $imageService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->createProduct($request->validated());
            
            // Create product folder in product-documents directory
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory("product-documents/{$product->product_code}");
            
            return new ProductResource($product);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Store product items under a product.
     */
    public function storeProductItems(Request $request, string $productCode)
    {
        // Check if product exists
        $product = Product::where('product_code', $productCode)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
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
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $createdProductItems = [];

            foreach ($request->productItems as $productItemData) {
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
                        \App\Models\ProductItemTranslation::create([
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

            return response()->json([
                'success' => true,
                'message' => 'Product items created successfully',
                'data' => $createdProductItems
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a product.
     */
    public function updateProduct(Request $request, string $productCode)
    {
        Log::info('UpdateProduct request:', $request->all());

        // Check if product exists
        $product = Product::where('product_code', $productCode)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'isActive' => 'sometimes|boolean',
            'cost' => 'sometimes|nullable|numeric|min:0',
            'costCurrency' => 'sometimes|nullable|string|size:3|exists:lkp_currency,code',
            'rrp' => 'sometimes|nullable|numeric|min:0',
            'rrpCurrency' => 'sometimes|nullable|string|size:3|exists:lkp_currency,code',
            'categories' => 'sometimes|array',
            'categories.*' => 'string|exists:lkp_category,category_code',
            'attributes' => 'sometimes|array',
            'attributes.*' => 'array', // Language code as key
            'attributes.*.*.name' => 'required|string|exists:lkp_attribute,name',
            'attributes.*.*.value' => 'required|string',
            'deliveries' => 'sometimes|array',
            'deliveries.*.min' => 'required|integer|min:0',
            'deliveries.*.max' => 'required|integer|min:0|gte:deliveries.*.min',
            'documents' => 'sometimes|array',
            'documents.*.type' => 'required|string|in:manual,technical,warranty',
            'documents.*.url' => 'required|string|max:500',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|exists:lkp_tag,tag_code',
            'itemTags' => 'sometimes|array',
            'itemTags.*' => 'string|exists:lkp_item_tag,item_tag_code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update product - only update fields that were provided
            $updateData = [];
            if ($request->has('isActive')) {
                $updateData['is_active'] = $request->isActive;
            }
            if ($request->has('cost')) {
                $updateData['cost'] = $request->cost;
            }
            if ($request->has('costCurrency')) {
                $updateData['cost_currency'] = $request->costCurrency;
            }
            if ($request->has('rrp')) {
                $updateData['rrp'] = $request->rrp;
            }
            if ($request->has('rrpCurrency')) {
                $updateData['rrp_currency'] = $request->rrpCurrency;
            }

            if (!empty($updateData)) {
                $product->update($updateData);
            }

            // Categories - Delete existing and recreate
            ProductCategory::where('product_code', $productCode)->delete();
            if (!empty($request->categories)) {
                foreach ($request->categories as $categoryCode) {
                    ProductCategory::create([
                        'product_code' => $productCode,
                        'category_code' => $categoryCode,
                    ]);
                }
            }

            // Attributes - Update existing or add new (DO NOT delete all first)
            try {
                $attributesInput = $request->input('attributes');
                if (!empty($attributesInput)) {
                    // Check if it's the grouped format (object with language keys)
                    if (!isset($attributesInput[0])) {
                        // Grouped format: {"lt": [{"name": "length", "value": "600"}], "en": [...]}
                        foreach ($attributesInput as $language => $attributes) {
                            if (!Language::where('code', $language)->exists()) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Invalid language code: ' . $language,
                                ], 422);
                            }
                            foreach ($attributes as $attribute) {
                            // Check if attribute-language combination exists
                            $existingAttribute = ProductAttributeValue::where('product_item_code', $productCode)
                                ->where('attribute_name', $attribute['name'])
                                ->where('language', $language)
                                ->first();

                            if ($existingAttribute) {
                                // Update existing attribute value using query builder for composite primary key
                                ProductAttributeValue::where('product_item_code', $productCode)
                                    ->where('attribute_name', $attribute['name'])
                                    ->where('language', $language)
                                    ->update(['value' => $attribute['value']]);
                            } else {
                                // Create new attribute for this language
                                ProductAttributeValue::create([
                                    'product_item_code' => $productCode,
                                    'attribute_name' => $attribute['name'],
                                    'language' => $language,
                                    'value' => $attribute['value'],
                                ]);
                            }
                            }
                        }
                    } elseif (is_array($attributesInput) && isset($attributesInput[0]) && is_array($attributesInput[0])) {
                        // Array format: [{"name": "length", "language": "en", "value": "600"}]
                        foreach ($attributesInput as $attribute) {
                            $language = $attribute['language'] ?? 'en';

                            // Check if attribute-language combination exists
                            $existingAttribute = ProductAttributeValue::where('product_item_code', $productCode)
                                ->where('attribute_name', $attribute['name'])
                                ->where('language', $language)
                                ->first();

                            if ($existingAttribute) {
                                // Update existing attribute value using query builder for composite primary key
                                ProductAttributeValue::where('product_item_code', $productCode)
                                    ->where('attribute_name', $attribute['name'])
                                    ->where('language', $language)
                                    ->update(['value' => $attribute['value']]);
                            } else {
                                // Create new attribute for this language
                                ProductAttributeValue::create([
                                    'product_item_code' => $productCode,
                                    'attribute_name' => $attribute['name'],
                                    'language' => $language,
                                    'value' => $attribute['value'],
                                ]);
                            }
                        }
                    } else {
                        // Old format: {"length": "600"} - assume 'en' language
                        foreach ($attributesInput as $attributeName => $value) {
                            // Check if attribute-language combination exists
                            $existingAttribute = ProductAttributeValue::where('product_item_code', $productCode)
                                ->where('attribute_name', $attributeName)
                                ->where('language', 'en')
                                ->first();

                            if ($existingAttribute) {
                                // Update existing attribute value using query builder for composite primary key
                                ProductAttributeValue::where('product_item_code', $productCode)
                                    ->where('attribute_name', $attributeName)
                                    ->where('language', 'en')
                                    ->update(['value' => $value]);
                            } else {
                                // Create new attribute for this language
                                ProductAttributeValue::create([
                                    'product_item_code' => $productCode,
                                    'attribute_name' => $attributeName,
                                    'language' => 'en',
                                    'value' => $value,
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error processing attributes',
                    'error' => $e->getMessage()
                ], 500);
            }

            // Deliveries - Delete existing and recreate
            ProductDelivery::where('isku', $productCode)->delete();
            if (!empty($request->deliveries)) {
                foreach ($request->deliveries as $domainCode => $delivery) {
                    ProductDelivery::create([
                        'isku' => $productCode,
                        'domain_id' => $domainCode,
                        'delivery_min' => $delivery['min'],
                        'delivery_max' => $delivery['max'],
                    ]);
                }
            }

            // Documents - Delete existing and recreate
            ProductDocument::where('product_code', $productCode)->delete();
            if (!empty($request->documents)) {
                foreach ($request->documents as $document) {
                    ProductDocument::create([
                        'product_code' => $productCode,
                        'doc_type' => $document['type'],
                        'file_url' => $document['url'],
                    ]);
                }
            }

            // Tags - Delete existing and recreate
            DB::table('product_tag')->where('product_code', $productCode)->delete();
            if (!empty($request->tags)) {
                foreach ($request->tags as $tagCode) {
                    DB::table('product_tag')->insert([
                        'product_code' => $productCode,
                        'tag_code' => $tagCode,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Item Tags - Delete existing and recreate
            DB::table('product_item_tag')->where('product_item_code', $productCode)->delete();
            if (!empty($request->itemTags)) {
                foreach ($request->itemTags as $itemTagCode) {
                    DB::table('product_item_tag')->insert([
                        'product_item_code' => $productCode,
                        'item_tag_code' => $itemTagCode,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load(['categories', 'attributeValues', 'deliveries', 'documents'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display the product with its product items.
     */
    public function showProduct(Request $request, string $productCode)
    {
        try {
        $language = $request->query('lang', 'en');

        $product = Product::with([
            'translations' => function ($query) use ($language) {
                $query->where('language', $language);
            },
            'tags'
        ])->where('product_code', $productCode)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Load product items with their relationships separately
        $productItems = ProductItem::with(['categories', 'attributeValues', 'deliveries', 'documents', 'translations' => function ($query) use ($language) {
            $query->where('language', $language);
        }, 'itemTags.translations' => function ($query) use ($language) {
            $query->where('language', $language);
        }])
            ->where('product_code', $productCode)
            ->get();

        // Load product categories
        $productCategories = \DB::table('product_category')
            ->where('product_code', $productCode)
            ->pluck('category_code')
            ->toArray();

        // Load related products for the main product (bidirectional) - optimized single query
        $productRelatedProducts = DB::table('product_related')
            ->where('relation_type', 'related')
            ->where(function ($query) use ($productCode) {
                $query->where(function ($q) use ($productCode) {
                    $q->where('from_entity_type', 'product')
                      ->where('from_entity_code', $productCode);
                })->orWhere(function ($q) use ($productCode) {
                    $q->where('to_entity_type', 'product')
                      ->where('to_entity_code', $productCode);
                });
            })
            ->selectRaw("
                CASE
                    WHEN from_entity_type = 'product' AND from_entity_code = ? THEN to_entity_type
                    WHEN to_entity_type = 'product' AND to_entity_code = ? THEN from_entity_type
                END as to_entity_type,
                CASE
                    WHEN from_entity_type = 'product' AND from_entity_code = ? THEN to_entity_code
                    WHEN to_entity_type = 'product' AND to_entity_code = ? THEN from_entity_code
                END as to_entity_code
            ", [$productCode, $productCode, $productCode, $productCode])
            ->distinct()
            ->get()
            ->map(function ($relation) {
                return [
                    'to_entity_type' => $relation->to_entity_type,
                    'to_entity_code' => $relation->to_entity_code,
                ];
            })
            ->toArray();

        // Get product images using ImageService
        $productImages = $this->imageService->getProductImages($productCode);

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
            'tags' => $product->tags ? $product->tags->map(function ($tag) use ($language) {
                $tagTranslation = \DB::table('lkp_tag_translation')
                    ->where('tag_code', $tag->tag_code)
                    ->where('language', $language)
                    ->first();
                $tagName = $tagTranslation ? $tagTranslation->name : $tag->tag_code;
                return [
                    'tag_code' => $tag->tag_code,
                    'tag_name' => $tagName,
                ];
            }) : [],
            'product_items' => $productItems->map(function ($productItem) use ($productCode, $language) {
                // Get product item images using ImageService
                $productItemImages = $this->imageService->getProductItemImages($productCode, $productItem->isku);

                // Load related products for this product item (bidirectional) - optimized single query
                $itemRelatedProducts = DB::table('product_related')
                    ->where('relation_type', 'related')
                    ->where(function ($query) use ($productItem) {
                        $query->where(function ($q) use ($productItem) {
                            $q->where('from_entity_type', 'product_item')
                              ->where('from_entity_code', $productItem->isku);
                        })->orWhere(function ($q) use ($productItem) {
                            $q->where('to_entity_type', 'product_item')
                              ->where('to_entity_code', $productItem->isku);
                        });
                    })
                    ->selectRaw("
                        CASE
                            WHEN from_entity_type = 'product_item' AND from_entity_code = ? THEN to_entity_type
                            WHEN to_entity_type = 'product_item' AND to_entity_code = ? THEN from_entity_type
                        END as to_entity_type,
                        CASE
                            WHEN from_entity_type = 'product_item' AND from_entity_code = ? THEN to_entity_code
                            WHEN to_entity_type = 'product_item' AND to_entity_code = ? THEN from_entity_code
                        END as to_entity_code
                    ", [$productItem->isku, $productItem->isku, $productItem->isku, $productItem->isku])
                    ->distinct()
                    ->get()
                    ->map(function ($relation) {
                        return [
                            'to_entity_type' => $relation->to_entity_type,
                            'to_entity_code' => $relation->to_entity_code,
                        ];
                    })
                    ->toArray();

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
                    'availability' => $productItem->availability, // Add availability field
                    'title' => $productItem->translations->where('language', $language)->first()?->title ?? $productItem->product_item_code,
                    'variation_text' => $productItem->translations->where('language', $language)->first()?->variation_text,
                    'images' => $productItemImages,
                    'related_products' => $itemRelatedProducts,
                    'categories' => $productItem->categories->pluck('category_code'),
                    'attributes' => $productItem->attributeValues->where('language', $language)->map(function ($attr) {
                        return [
                            'name' => $attr->attribute_name,
                            'value' => $attr->value,
                        ];
                    }),
                    'deliveries' => $productItem->deliveries->map(function ($delivery) {
                        return [
                            'domain' => $delivery->domain_id,
                            'min' => $delivery->delivery_min,
                            'max' => $delivery->delivery_max,
                        ];
                    }),
                    'documents' => $productItem->documents->map(function ($document) {
                        return [
                            'type' => $document->doc_type,
                            'url' => $document->file_url,
                        ];
                    }),
                    'tags' => $productItem->itemTags->map(function ($itemTag) use ($language) {
                        $tagName = $itemTag->translations->where('language', $language)->first()?->name ?? $itemTag->item_tag_code;
                        return [
                            'tag_code' => $itemTag->item_tag_code,
                            'tag_name' => $tagName,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $normalizedData
        ], 200);
    }
    catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting product details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload images for product.
     */
    public function uploadProductImages(Request $request, string $productCode)
    {
        // Check if product exists
        $product = Product::where('product_code', $productCode)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $index => $image) {
            // New naming convention: productcode_Main for first image, productcode_1, productcode_2, etc. for others
            $suffix = $index === 0 ? 'Main' : '_' . $index;
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
    }

    /**
     * Upload images for product item.
     */
    public function uploadProductItemImages(Request $request, string $productItemCode)
    {
        // Check if product item exists
        $productItem = ProductItem::where('product_item_code', $productItemCode)->first();
        if (!$productItem) {
            return response()->json([
                'success' => false,
                'message' => 'Product item not found'
            ], 404);
        }

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $index => $image) {
            // New naming convention: isku_Main for first image, isku_1, isku_2, etc. for others
            $suffix = $index === 0 ? 'Main' : '_' . $index;
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
    }

    /**
     * Process Excel file and create products
     */
    public function processExcel(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|string',
                'lang' => 'required|string|size:2|exists:lkp_language,code'
            ]);

            $fileName = $request->input('file');
            $language = $request->input('lang');

            // Check if file exists
            $filePath = storage_path('app/public/excel/' . $fileName);
            if (!file_exists($filePath)) {
                $altFilePath = storage_path('app/public/' . $fileName);
                if (file_exists($altFilePath)) {
                    $filePath = $altFilePath;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Excel file not found. Please place the file in storage/app/public/excel/ or storage/app/public/ directory',
                        'searched_paths' => [
                            'storage/app/public/excel/' . $fileName,
                            'storage/app/public/' . $fileName
                        ]
                    ], 404);
                }
            }

            $excelImportService = app(ExcelImportService::class);
            $result = $excelImportService->processExcelFile($filePath, $language);

            return response()->json([
                'success' => true,
                'message' => 'Excel file processed successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process Excel file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert meters to millimeters
     */
    private function convertToMm($value)
    {
        try {
            if (empty($value)) return null;

            // If value is already in mm (contains 'mm'), return as is
            if (strpos(strtolower($value), 'mm') !== false) {
                return str_replace(['mm', ' '], '', $value);
            }

            // If value is in meters (contains 'm'), convert to mm
            if (strpos(strtolower($value), 'm') !== false) {
                $numericValue = floatval(str_replace(['m', ' '], '', $value));
                return $numericValue * 1000; // Convert m to mm
            }

            // If no unit specified, assume it's in meters and convert to mm
            $numericValue = floatval($value);
            return $numericValue * 1000;
        } catch (\Exception $e) {
            \Log::error('Error in convertToMm', [
                'value' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Parse categories from comma-separated string
     * Handles values that may contain commas within quotes or escaped
     */
    private function parseCategories($categoriesString)
    {
        try {
            if (empty($categoriesString)) return [];

            // If the string contains quotes, handle quoted values
            if (strpos($categoriesString, '"') !== false) {
                $categories = [];
                $inQuotes = false;
                $current = '';

                for ($i = 0; $i < strlen($categoriesString); $i++) {
                    $char = $categoriesString[$i];

                    if ($char === '"') {
                        $inQuotes = !$inQuotes;
                    } elseif ($char === ',' && !$inQuotes) {
                        $categories[] = trim($current);
                        $current = '';
                    } else {
                        $current .= $char;
                    }
                }

                if (!empty($current)) {
                    $categories[] = trim($current);
                }

                return array_filter($categories); // Remove empty values
            }

            // Simple comma splitting for unquoted values
            return array_filter(array_map('trim', explode(',', $categoriesString)));
        } catch (\Exception $e) {
            \Log::error('Error in parseCategories', [
                'categoriesString' => $categoriesString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return []; // Return empty array on error
        }
    }

    /**
     * Parse tags from comma-separated string
     * Handles values that may contain commas within quotes or escaped
     */
    private function parseTags($tagsString)
    {
        try {
            if (empty($tagsString)) return [];

            // If the string contains quotes, handle quoted values
            if (strpos($tagsString, '"') !== false) {
                $tags = [];
                $inQuotes = false;
                $current = '';

                for ($i = 0; $i < strlen($tagsString); $i++) {
                    $char = $tagsString[$i];

                    if ($char === '"') {
                        $inQuotes = !$inQuotes;
                    } elseif ($char === ',' && !$inQuotes) {
                        $tags[] = trim($current);
                        $current = '';
                    } else {
                        $current .= $char;
                    }
                }

                if (!empty($current)) {
                    $tags[] = trim($current);
                }

                return array_filter($tags); // Remove empty values
            }

            // Simple comma splitting for unquoted values
            return array_filter(array_map('trim', explode(',', $tagsString)));
        } catch (\Exception $e) {
            \Log::error('Error in parseTags', [
                'tagsString' => $tagsString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return []; // Return empty array on error
        }
    }

    /**
     * Parse related products from comma-separated string
     * Handles values that may contain commas within quotes or escaped
     */
    private function parseRelatedProducts($relatedProductsString)
    {
        try {
            if (empty($relatedProductsString)) return [];

            // If the string contains quotes, handle quoted values
            if (strpos($relatedProductsString, '"') !== false) {
                $products = [];
                $inQuotes = false;
                $current = '';

                for ($i = 0; $i < strlen($relatedProductsString); $i++) {
                    $char = $relatedProductsString[$i];

                    if ($char === '"') {
                        $inQuotes = !$inQuotes;
                    } elseif ($char === ',' && !$inQuotes) {
                        $products[] = trim($current);
                        $current = '';
                    } else {
                        $current .= $char;
                    }
                }

                if (!empty($current)) {
                    $products[] = trim($current);
                }

                return array_filter($products); // Remove empty values
            }

            // Simple comma splitting for unquoted values
            return array_filter(array_map('trim', explode(',', $relatedProductsString)));
        } catch (\Exception $e) {
            \Log::error('Error in parseRelatedProducts', [
                'relatedProductsString' => $relatedProductsString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return []; // Return empty array on error
        }
    }

    /**
     * Parse availability from "Stock/On demand" column
     * Returns 's' for stock items, 'o' for on-demand items
     * Accepts direct values 's'/'o' (case insensitive) or full words
     * Defaults to 'o' (on-demand) when missing
     */
    private function parseAvailability($availabilityString)
    {
        try {
            if (empty($availabilityString)) return 'o'; // Default to on-demand

            $normalized = strtolower(trim($availabilityString));

            // Check for direct 's' or 'o' values first (case insensitive)
            if ($normalized === 's') {
                return 's';
            } elseif ($normalized === 'o') {
                return 'o';
            }

            // Check for various forms of "stock" or "on demand"
            if (strpos($normalized, 'stock') !== false) {
                return 's';
            } elseif (strpos($normalized, 'on demand') !== false ||
                     strpos($normalized, 'on-demand') !== false ||
                     strpos($normalized, 'ondemand') !== false) {
                return 'o';
            }

            // Default to on-demand if unclear
            return 'o';
        } catch (\Exception $e) {
            \Log::error('Error in parseAvailability', [
                'availabilityString' => $availabilityString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 'o'; // Return default on error
        }
    }

    /**
     * Normalize string by removing spaces, dashes, and underscores for matching
     */
    private function normalizeForMatching($string)
    {
        return preg_replace('/[\s\-_]/', '', strtolower($string));
    }

    /**
     * Get or create category (case insensitive, ignores spaces/dashes/underscores)
     */
    private function getOrCreateCategory($categoryName, $language)
    {
        try {
            if (empty($categoryName)) return null;

            $normalizedInput = $this->normalizeForMatching($categoryName);

            // Check if category exists (case insensitive, ignoring spaces/dashes/underscores)
            $existingCategory = \DB::table('lkp_category')
                ->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(category_code, \' \', \'\'), \'-\', \'\'), \'_\', \'\')) = ?', [$normalizedInput])
                ->first();

            if ($existingCategory) {
                return $existingCategory->category_code;
            }

            // Check if category exists by translation (case insensitive, ignoring spaces/dashes/underscores)
            $existingTranslation = \DB::table('lkp_category_translation')
                ->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(name, \' \', \'\'), \'-\', \'\'), \'_\', \'\')) = ?', [$normalizedInput])
                ->where('language', $language)
                ->first();

            if ($existingTranslation) {
                return $existingTranslation->category_code;
            }

            // Create new category
            $categoryCode = strtoupper(str_replace(' ', '_', $categoryName));

            // Ensure unique category code
            $counter = 1;
            $originalCode = $categoryCode;
            while (\DB::table('lkp_category')->where('category_code', $categoryCode)->exists()) {
                $categoryCode = $originalCode . '_' . $counter;
                $counter++;
            }

            // Insert category
            \DB::table('lkp_category')->insert([
                'category_code' => $categoryCode,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert category translation
            \DB::table('lkp_category_translation')->insert([
                'category_code' => $categoryCode,
                'language' => $language,
                'name' => $categoryName,
            ]);

            return $categoryCode;
        } catch (\Exception $e) {
            \Log::error('Error in getOrCreateCategory', [
                'categoryName' => $categoryName,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Get or create tag (case insensitive)
     */
    private function getOrCreateTag($tagName, $language)
    {
        try {
            if (empty($tagName)) return null;

            // Check if tag exists (case insensitive)
            $existingTag = \DB::table('lkp_tag')
                ->whereRaw('LOWER(tag_code) = ?', [strtolower($tagName)])
                ->first();

            if ($existingTag) {
                return $existingTag->tag_code;
            }

            // Check if tag exists by translation (case insensitive)
            $existingTranslation = \DB::table('lkp_tag_translation')
                ->whereRaw('LOWER(name) = ?', [strtolower($tagName)])
                ->where('language', $language)
                ->first();

            if ($existingTranslation) {
                return $existingTranslation->tag_code;
            }

            // Create new tag
            $tagCode = strtoupper(str_replace(' ', '_', $tagName));

            // Ensure unique tag code
            $counter = 1;
            $originalCode = $tagCode;
            while (\DB::table('lkp_tag')->where('tag_code', $tagCode)->exists()) {
                $tagCode = $originalCode . '_' . $counter;
                $counter++;
            }

            // Insert tag
            \DB::table('lkp_tag')->insert([
                'tag_code' => $tagCode,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert tag translation
            \DB::table('lkp_tag_translation')->insert([
                'tag_code' => $tagCode,
                'language' => $language,
                'name' => $tagName,
            ]);

            return $tagCode;
        } catch (\Exception $e) {
            \Log::error('Error in getOrCreateTag', [
                'tagName' => $tagName,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Get or create item tag (case insensitive)
     */
    private function getOrCreateItemTag($itemTagName, $language)
    {
        try {
            if (empty($itemTagName)) return null;

            // Check if item tag exists (case insensitive)
            $existingItemTag = \DB::table('lkp_item_tag')
                ->whereRaw('LOWER(item_tag_code) = ?', [strtolower($itemTagName)])
                ->first();

            if ($existingItemTag) {
                return $existingItemTag->item_tag_code;
            }

            // Check if item tag exists by translation (case insensitive)
            $existingTranslation = \DB::table('lkp_item_tag_translation')
                ->whereRaw('LOWER(name) = ?', [strtolower($itemTagName)])
                ->where('language', $language)
                ->first();

            if ($existingTranslation) {
                return $existingTranslation->item_tag_code;
            }

            // Create new item tag
            $itemTagCode = strtoupper(str_replace(' ', '_', $itemTagName));

            // Ensure unique item tag code
            $counter = 1;
            $originalCode = $itemTagCode;
            while (\DB::table('lkp_item_tag')->where('item_tag_code', $itemTagCode)->exists()) {
                $itemTagCode = $originalCode . '_' . $counter;
                $counter++;
            }

            // Insert item tag
            \DB::table('lkp_item_tag')->insert([
                'item_tag_code' => $itemTagCode,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert item tag translation
            \DB::table('lkp_item_tag_translation')->insert([
                'item_tag_code' => $itemTagCode,
                'language' => $language,
                'name' => $itemTagName,
            ]);

            return $itemTagCode;
        } catch (\Exception $e) {
            \Log::error('Error in getOrCreateItemTag', [
                'itemTagName' => $itemTagName,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Ensure attribute exists in lkp_attribute table (outside transaction)
     */
    private function ensureAttributeExists($attributeName)
    {
        try {
            if (empty($attributeName)) return;

            // Check if attribute exists (case insensitive)
            $existingAttribute = \DB::table('lkp_attribute')
                ->whereRaw('LOWER(name) = ?', [strtolower($attributeName)])
                ->first();

            if (!$existingAttribute) {
                // Create new attribute outside current transaction
                \DB::transaction(function () use ($attributeName) {
                    \DB::table('lkp_attribute')->insert([
                        'name' => $attributeName,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
            }
        } catch (\Exception $e) {
            \Log::error('Error in ensureAttributeExists', [
                'attributeName' => $attributeName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - just log the error
        }
    }

    /**
     * Get or create attribute (case insensitive)
     */
    private function getOrCreateAttribute($attributeName)
    {
        try {
            if (empty($attributeName)) return null;

            // Check if attribute exists (case insensitive)
            $existingAttribute = \DB::table('lkp_attribute')
                ->whereRaw('LOWER(name) = ?', [strtolower($attributeName)])
                ->first();

            if ($existingAttribute) {
                return $existingAttribute->name;
            }

            // Create new attribute
            \DB::table('lkp_attribute')->insert([
                'name' => $attributeName,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $attributeName;
        } catch (\Exception $e) {
            \Log::error('Error in getOrCreateAttribute', [
                'attributeName' => $attributeName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Get column value by trying multiple possible column names
     */
    private function getColumnValue($columnMapping, $row, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            if (isset($columnMapping[$name])) {
                return isset($row[$columnMapping[$name]]) ? $row[$columnMapping[$name]] : null;
            }
        }
        return null; // Return null if none of the column names are found
    }

    /**
     * Get related products/items for a given product or product item
     */
    public function getRelatedProducts(Request $request)
    {
        try {
            $request->validate([
                'entity_code' => 'required|string',
                'lang' => 'required|string|size:2|exists:lkp_language,code'
            ]);

            $entityCode = $request->input('entity_code');
            $language = $request->input('lang');

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
                return response()->json([
                    'success' => false,
                    'message' => 'Entity not found'
                ], 404);
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
                        $imageUrl = $this->imageService->getProductImageUrlForRelated($product->product_code);

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
                        $imageUrl = $this->imageService->getProductItemImageUrlForRelated($productItem->product_code, $productItem->isku);

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

            return response()->json([
                'success' => true,
                'data' => $response
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
     * Get product items by category codes, or products if no items exist
     */
    public function getProductsByCategories(Request $request)
    {
        try {
            $request->validate([
                'category_codes' => 'required|array|min:1',
                'category_codes.*' => 'string|exists:lkp_category,category_code',
                'lang' => 'required|string|size:2|exists:lkp_language,code',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100'
            ]);

            $categoryCodes = $request->input('category_codes');
            $language = $request->input('lang');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20); // Default 20 items per page

            // First, check if there are any product items for the given categories
            $hasProductItems = \DB::table('product_category')
                ->whereIn('category_code', $categoryCodes)
                ->join('product_item', 'product_category.product_code', '=', 'product_item.product_code')
                ->exists();

            if ($hasProductItems) {
                // Return product items (existing logic)
                $query = \DB::table('product_category')
                    ->whereIn('category_code', $categoryCodes)
                    ->join('product_item', 'product_category.product_code', '=', 'product_item.product_code')
                    ->leftJoin('product_item_translation', function ($join) use ($language) {
                        $join->on('product_item.isku', '=', 'product_item_translation.isku')
                             ->where('product_item_translation.language', '=', $language);
                    })
                    ->select(
                        'product_item.product_item_code',
                        'product_item.isku',
                        'product_item.product_code',
                        'product_item.cost',
                        'product_item.cost_currency',
                        'product_item.rrp',
                        'product_item.rrp_currency',
                        'product_item_translation.title'
                    );

                // Apply pagination if requested
                if ($request->has('page') || $request->has('per_page')) {
                    $paginatedItems = $query->paginate($perPage, ['*'], 'page', $page);

                    $response = [];
                    foreach ($paginatedItems->items() as $item) {
                    // Get product item image using ImageService
                    $imageUrl = $this->imageService->getProductItemImageUrlForCategories($item->product_code, $item->isku);

                        $response[] = [
                            'product_item_code' => $item->product_item_code,
                            'isku' => $item->isku,
                            'product_code' => $item->product_code,
                            'image' => $imageUrl,
                            'cost' => $item->cost,
                            'cost_currency' => $item->cost_currency,
                            'rrp' => $item->rrp,
                            'rrp_currency' => $item->rrp_currency,
                            'product_item_name' => $item->title ?? $item->product_item_code,
                        ];
                    }

                    return response()->json([
                        'success' => true,
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
                    ], 200);
                } else {
                    // Return all results without pagination
                    $productItems = $query->get();

                    $response = [];
                    foreach ($productItems as $item) {
                    // Get product item image using ImageService
                    $imageUrl = $this->imageService->getProductItemImageUrlForCategories($item->product_code, $item->isku);

                        $response[] = [
                            'product_item_code' => $item->product_item_code,
                            'isku' => $item->isku,
                            'product_code' => $item->product_code,
                            'image' => $imageUrl,
                            'cost' => $item->cost,
                            'cost_currency' => $item->cost_currency,
                            'rrp' => $item->rrp,
                            'rrp_currency' => $item->rrp_currency,
                            'product_item_name' => $item->title ?? $item->product_item_code,
                        ];
                    }

                    return response()->json([
                        'success' => true,
                        'data' => $response
                    ], 200);
                }
            } else {
                // No product items found, return products instead
                $query = \DB::table('product_category')
                    ->whereIn('category_code', $categoryCodes)
                    ->join('product', 'product_category.product_code', '=', 'product.product_code')
                    ->leftJoin('product_translation', function ($join) use ($language) {
                        $join->on('product.product_code', '=', 'product_translation.product_code')
                             ->where('product_translation.language', '=', $language);
                    })
                    ->select(
                        'product.product_code',
                        'product.created_at',
                        'product.updated_at',
                        'product_translation.title',
                        'product_translation.summary',
                        'product_translation.description'
                    );

                // Apply pagination if requested
                if ($request->has('page') || $request->has('per_page')) {
                    $paginatedProducts = $query->paginate($perPage, ['*'], 'page', $page);

                    $response = [];
                    foreach ($paginatedProducts->items() as $product) {
                        // Get product image using ImageService
                        $imageUrl = $this->imageService->getProductImageUrlForCategories($product->product_code);

                        $response[] = [
                            'product_code' => $product->product_code,
                            'image' => $imageUrl,
                            'title' => $product->title ?? $product->product_code,
                            'summary' => $product->summary,
                            'description' => $product->description,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                        ];
                    }

                    return response()->json([
                        'success' => true,
                        'data' => $response,
                        'pagination' => [
                            'current_page' => $paginatedProducts->currentPage(),
                            'per_page' => $paginatedProducts->perPage(),
                            'total' => $paginatedProducts->total(),
                            'last_page' => $paginatedProducts->lastPage(),
                            'from' => $paginatedProducts->firstItem(),
                            'to' => $paginatedProducts->lastItem(),
                            'has_more_pages' => $paginatedProducts->hasMorePages(),
                            'prev_page_url' => $paginatedProducts->previousPageUrl(),
                            'next_page_url' => $paginatedProducts->nextPageUrl(),
                        ]
                    ], 200);
                } else {
                    // Return all results without pagination
                    $products = $query->get();

                    $response = [];
                    foreach ($products as $product) {
                        // Get product image using ImageService
                        $imageUrl = $this->imageService->getProductImageUrlForCategories($product->product_code);

                        $response[] = [
                            'product_code' => $product->product_code,
                            'image' => $imageUrl,
                            'title' => $product->title ?? $product->product_code,
                            'summary' => $product->summary,
                            'description' => $product->description,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                        ];
                    }

                    return response()->json([
                        'success' => true,
                        'data' => $response
                    ], 200);
                }
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting products by categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get parent product code for a given ISKU
     */
    public function getProductCodeByIsku(Request $request)
    {
        try {
            $request->validate([
                'isku' => 'required|string|exists:product_item,isku'
            ]);

            $isku = $request->input('isku');

            // Get the product item and its product code
            $productItem = \DB::table('product_item')
                ->where('isku', $isku)
                ->select('product_code')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'isku' => $isku,
                    'product_code' => $productItem->product_code
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
     * Get products by codes with title, image, and categories based on language
     */
    public function getProductsByCodes(Request $request)
    {
        try {
            $request->validate([
                'language' => 'required|string|size:2|exists:lkp_language,code',
                'product_codes' => 'required|array|min:1',
                'product_codes.*' => 'string|exists:product,product_code'
            ]);

            $language = $request->input('language');
            $productCodes = $request->input('product_codes');

            $products = Product::with([
                'translations' => function ($query) use ($language) {
                    $query->where('language', $language);
                }
            ])->whereIn('product_code', $productCodes)->get();

            $response = [];
            foreach ($products as $product) {
                        // Get product image using ImageService
                        $imageUrl = $this->imageService->getProductImageUrlForCategories($product->product_code);

                // Get product categories
                $categories = \DB::table('product_category')
                    ->where('product_code', $product->product_code)
                    ->pluck('category_code')
                    ->toArray();

                $response[] = [
                    'code' => $product->product_code,
                    'title' => $product->translations->first()?->title ?? $product->product_code,
                    'image' => $imageUrl,
                    'categories' => $categories,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting products by codes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product items for a specific product code
     */
    public function getProductItems(Request $request, string $productCode)
    {
        try {
            $request->validate([
                'language' => 'required|string|size:2|exists:lkp_language,code',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100'
            ]);

            $language = $request->input('language');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20); // Default 20 items per page

            // Check if product exists
            $product = Product::where('product_code', $productCode)->first();
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $query = ProductItem::with([
                'translations' => function ($query) use ($language) {
                    $query->where('language', $language);
                }
            ])->where('product_code', $productCode);

            // Apply pagination if requested
            if ($request->has('page') || $request->has('per_page')) {
                $paginatedItems = $query->paginate($perPage, ['*'], 'page', $page);

                $response = [];
                foreach ($paginatedItems->items() as $productItem) {
                    // Get product item image using ImageService
                    $imageUrl = $this->imageService->getProductItemImageUrlForProductItems($productCode, $productItem->isku);

                    $response[] = [
                        'item_code' => $productItem->product_item_code,
                        'isku' => $productItem->isku,
                        'title' => $productItem->translations->first()?->title ?? $productItem->product_item_code,
                        'cost' => $productItem->cost,
                        'rrp' => $productItem->rrp,
                        'cost_currency' => $productItem->cost_currency,
                        'rrp_currency' => $productItem->rrp_currency,
                        'image' => $imageUrl,
                    ];
                }

                return response()->json([
                    'success' => true,
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
                ], 200);
            } else {
                // Return all results without pagination
                $productItems = $query->get();

                $response = [];
                foreach ($productItems as $productItem) {
                    // Get product item image using ImageService
                    $imageUrl = $this->imageService->getProductItemImageUrlForProductItems($productCode, $productItem->isku);

                    $response[] = [
                        'item_code' => $productItem->product_item_code,
                        'isku' => $productItem->isku,
                        'title' => $productItem->translations->first()?->title ?? $productItem->product_item_code,
                        'cost' => $productItem->cost,
                        'rrp' => $productItem->rrp,
                        'cost_currency' => $productItem->cost_currency,
                        'rrp_currency' => $productItem->rrp_currency,
                        'image' => $imageUrl,
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => $response
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting product items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get entities (products and product items) by tag code
     */
    public function getEntitiesByTag(Request $request, string $tagCode, string $lang)
    {
        try {
            // Validate language exists
            if (!\DB::table('lkp_language')->where('code', $lang)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid language code'
                ], 422);
            }

            $language = $lang;

            $entities = [];

            // First, check if tag_code exists in lkp_tag (for products)
            $tag = \DB::table('lkp_tag')
                ->where('tag_code', $tagCode)
                ->first();

            if ($tag) {
                // Get products associated with this tag
                $productCodes = \DB::table('product_tag')
                    ->where('tag_code', $tagCode)
                    ->pluck('product_code')
                    ->toArray();

                foreach ($productCodes as $productCode) {
                    $product = Product::with(['translations' => function ($query) use ($language) {
                        $query->where('language', $language);
                    }])->where('product_code', $productCode)->first();

                    if ($product) {
                        // Get product image using ImageService
                        $imageUrl = $this->imageService->getProductImageUrlForCategories($product->product_code);

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
            }

            // Second, check if tag_code exists in lkp_item_tag (for product items)
            $itemTag = \DB::table('lkp_item_tag')
                ->where('item_tag_code', $tagCode)
                ->first();

            if ($itemTag) {
                // Get item tag name from translations
                $itemTagTranslation = \DB::table('lkp_item_tag_translation')
                    ->where('item_tag_code', $tagCode)
                    ->where('language', $language)
                    ->first();
                $itemTagName = $itemTagTranslation ? $itemTagTranslation->name : $tagCode;

                // Get product items associated with this item tag
                $iskus = \DB::table('product_item_tag')
                    ->where('item_tag_code', $tagCode)
                    ->pluck('isku')
                    ->toArray();

                foreach ($iskus as $isku) {
                    $productItem = ProductItem::with(['translations' => function ($query) use ($language) {
                        $query->where('language', $language);
                    }])->where('isku', $isku)->first();

                    if ($productItem) {
                        // Get product item image using ImageService
                        $imageUrl = $this->imageService->getProductItemImageUrlForCategories($productItem->product_code, $productItem->isku);

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
            }

            // If no tag found in either table, return empty array
            if (!$tag && !$itemTag) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ], 200);
            }

            // Get the tag name based on language
            $tagName = null;
            if ($tag) {
                $tagTranslation = \DB::table('lkp_tag_translation')
                    ->where('tag_code', $tagCode)
                    ->where('language', $language)
                    ->first();
                $tagName = $tagTranslation ? $tagTranslation->name : $tagCode;
            } elseif ($itemTag) {
                $itemTagTranslation = \DB::table('lkp_item_tag_translation')
                    ->where('item_tag_code', $tagCode)
                    ->where('language', $language)
                    ->first();
                $tagName = $itemTagTranslation ? $itemTagTranslation->name : $tagCode;
            }

            return response()->json([
                'success' => true,
                'tag_name' => $tagName,
                'data' => $entities
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting entities by tag',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download and organize product images from Google Drive
     */
    public function downloadProductImages(Request $request)
    {
        try {
            $request->validate([
                'drive_url' => 'required|url'
            ]);

            $driveUrl = $request->input('drive_url');

            // Create temporary directory for download
            $tempDir = '/tmp/drive_images_' . time();
            if (!mkdir($tempDir, 0755, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create temporary directory'
                ], 500);
            }

            // Execute gdown command to download the folder
            $command = "gdown --folder \"$driveUrl\" -O \"$tempDir\" 2>&1";
            $output = shell_exec($command);

            if ($output === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to execute download command'
                ], 500);
            }

            // Check if download was successful by looking for downloaded files
            $downloadedFiles = [];
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempDir));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $downloadedFiles[] = $file->getPathname();
                }
            }

            if (empty($downloadedFiles)) {
                // Clean up temp directory
                shell_exec("rm -rf \"$tempDir\"");
                return response()->json([
                    'success' => false,
                    'message' => 'No files were downloaded from Google Drive'
                ], 400);
            }

            $processedProducts = 0;
            $processedImages = 0;

            // Process each product directory
            $productDirs = glob("$tempDir/*", GLOB_ONLYDIR);
            foreach ($productDirs as $productDir) {
                $productCode = basename($productDir);

                // Create product directory in storage
                Storage::disk('public')->makeDirectory("products/$productCode");

                // Process product images (files directly in product directory)
                $productFiles = glob("$productDir/*.jpg") + glob("$productDir/*.jpeg") + glob("$productDir/*.png") + glob("$productDir/*.gif") + glob("$productDir/*.bmp") + glob("$productDir/*.webp");
                $imageIndex = 1;

                foreach ($productFiles as $file) {
                    $filename = basename($file);
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Skip files that don't match the expected pattern
                    if (!preg_match('/^' . preg_quote($productCode, '/') . '(_Main|_\\d+)?\.' . $extension . '$/', $filename)) {
                        // Rename file to match expected pattern
                        if (strpos($filename, '_Main') !== false) {
                            $newFilename = $productCode . '_Main.' . $extension;
                        } else {
                            $newFilename = $productCode . '_' . $imageIndex . '.' . $extension;
                            $imageIndex++;
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

                // Process variant directories (product item images)
                $variantDirs = glob("$productDir/variant/*", GLOB_ONLYDIR) + glob("$productDir/Variant/*", GLOB_ONLYDIR);
                foreach ($variantDirs as $variantDir) {
                    $isku = basename($variantDir);

                    // Create variant directory
                    Storage::disk('public')->makeDirectory("products/$productCode/variant/$isku");

                    // Process variant images
                    $variantFiles = glob("$variantDir/*.jpg") + glob("$variantDir/*.jpeg") + glob("$variantDir/*.png") + glob("$variantDir/*.gif") + glob("$variantDir/*.bmp") + glob("$variantDir/*.webp");
                    $variantImageIndex = 1;

                    foreach ($variantFiles as $file) {
                        $filename = basename($file);
                        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                        // Skip files that don't match the expected pattern
                        if (!preg_match('/^' . preg_quote($isku, '/') . '(_Main|_\\d+)?\.' . $extension . '$/', $filename)) {
                            // Rename file to match expected pattern
                            if (strpos($filename, '_Main') !== false) {
                                $newFilename = $isku . '_Main.' . $extension;
                            } else {
                                $newFilename = $isku . '_' . $variantImageIndex . '.' . $extension;
                                $variantImageIndex++;
                            }

                            $newPath = dirname($file) . '/' . $newFilename;
                            rename($file, $newPath);
                            $file = $newPath;
                            $filename = $newFilename;
                        }

                        // Copy to storage
                        $storagePath = "products/$productCode/variant/$isku/$filename";
                        if (Storage::disk('public')->put($storagePath, file_get_contents($file))) {
                            $processedImages++;
                        }
                    }
                }

                $processedProducts++;
            }

            // Clean up temporary directory
            shell_exec("rm -rf \"$tempDir\"");

            return response()->json([
                'success' => true,
                'message' => 'Images downloaded and organized successfully',
                'data' => [
                    'products_processed' => $processedProducts,
                    'images_processed' => $processedImages,
                    'drive_url' => $driveUrl
                ]
            ], 200);

        } catch (\Exception $e) {
            // Clean up temp directory on error
            if (isset($tempDir) && file_exists($tempDir)) {
                shell_exec("rm -rf \"$tempDir\"");
            }

            return response()->json([
                'success' => false,
                'message' => 'Error downloading images from Google Drive',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product documents for a specific product and language
     */
    public function getProductDocuments(GetProductDocumentsRequest $request)
    {
        try {
            $productCode = $request->input('product_code');
            $language = $request->input('lang');
            $purpose = $request->input('purpose', 'manual'); // Default to 'manual' if empty

            $documentsData = $this->productService->getProductDocuments($productCode, $language, $purpose);

            return new \App\Http\Resources\ProductDocumentsResource($documentsData);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
