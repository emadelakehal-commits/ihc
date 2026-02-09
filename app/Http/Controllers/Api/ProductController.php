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
use App\Services\ProductItemService;
use App\Services\ProductDetailsService;
use App\Services\ProductCategoryService;
use App\Services\ProductTagService;
use App\Services\ProductImageDownloadService;
use App\Services\ProductUtilityService;
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
        try {
            $productItemService = app(ProductItemService::class);
            $createdProductItems = $productItemService->storeProductItems($productCode, $request->productItems);

            return response()->json([
                'success' => true,
                'message' => 'Product items created successfully',
                'data' => $createdProductItems
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product items',
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
            
            $productDetailsService = app(ProductDetailsService::class);
            $productData = $productDetailsService->getProductDetails($productCode, $language);

            return response()->json([
                'success' => true,
                'data' => $productData
            ], 200);
        } catch (\Exception $e) {
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
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->convertToMm($value);
    }

    /**
     * Parse categories from comma-separated string
     * Handles values that may contain commas within quotes or escaped
     */
    private function parseCategories($categoriesString)
    {
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->parseCategories($categoriesString);
    }

    /**
     * Parse tags from comma-separated string
     * Handles values that may contain commas within quotes or escaped
     */
    private function parseTags($tagsString)
    {
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->parseTags($tagsString);
    }

    /**
     * Get or create category (case insensitive)
     */
    private function getOrCreateCategory($categoryName, $language)
    {
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->getOrCreateCategory($categoryName, $language);
    }

    /**
     * Get or create tag (case insensitive)
     */
    private function getOrCreateTag($tagName, $language)
    {
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->getOrCreateTag($tagName, $language);
    }

    /**
     * Get or create item tag (case insensitive)
     */
    private function getOrCreateItemTag($itemTagName, $language)
    {
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->getOrCreateItemTag($itemTagName, $language);
    }

    /**
     * Ensure attribute exists in lkp_attribute table (outside transaction)
     */
    private function ensureAttributeExists($attributeName)
    {
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->ensureAttributeExists($attributeName);
    }

    /**
     * Get or create attribute (case insensitive)
     */
    private function getOrCreateAttribute($attributeName)
    {
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->getOrCreateAttribute($attributeName);
    }

    /**
     * Get column value by trying multiple possible column names
     */
    private function getColumnValue($columnMapping, $row, $possibleNames)
    {
        $productUtilityService = app(ProductUtilityService::class);
        return $productUtilityService->getColumnValue($columnMapping, $row, $possibleNames);
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

            $productCategoryService = app(ProductCategoryService::class);
            $result = $productCategoryService->getProductsByCategories($categoryCodes, $language, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ], 200);

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
            $productTagService = app(ProductTagService::class);
            $result = $productTagService->getEntitiesByTag($tagCode, $lang);

            return response()->json([
                'success' => true,
                'tag_name' => $result['tag_name'],
                'data' => $result['data']
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
            $productImageDownloadService = app(ProductImageDownloadService::class);
            $result = $productImageDownloadService->downloadProductImages($request->input('drive_url'));

            return response()->json([
                'success' => true,
                'message' => 'Images downloaded and organized successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
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
