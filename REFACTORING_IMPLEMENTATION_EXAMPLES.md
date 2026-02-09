# Refactoring Implementation Examples

## Before and After Code Examples

This document shows specific examples of how the current monolithic controller code would be refactored using the proposed design patterns.

## Example 1: Image Upload Methods

### Before (Current ProductController - ~50 lines)
```php
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
```

### After (Refactored - ProductImageController)

#### Controller (15 lines)
```php
class ProductImageController extends Controller
{
    public function __construct(private ProductImageService $imageService) {}

    public function uploadProductImages(UploadProductImagesRequest $request, string $productCode): JsonResponse
    {
        $images = $this->imageService->uploadProductImages($productCode, $request->file('images'));
        return response()->json(['data' => $images]);
    }
}
```

#### Service (35 lines)
```php
class ProductImageService
{
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

    private function validateProductExists(string $productCode): void
    {
        if (!Product::where('product_code', $productCode)->exists()) {
            throw new ProductNotFoundException("Product {$productCode} not found");
        }
    }

    private function validateImageFiles(array $images): void
    {
        foreach ($images as $image) {
            if (!$image->isValid()) {
                throw new InvalidImageException("Invalid image file provided");
            }
        }
    }
}
```

#### Request Validation (10 lines)
```php
class UploadProductImagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
```

## Example 2: Excel Import Processing

### Before (Current ProductController - ~100 lines)
```php
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
```

### After (Refactored - ProductImportController)

#### Controller (20 lines)
```php
class ProductImportController extends Controller
{
    public function __construct(
        private ProductImportService $importService,
        private ImportProductCommandHandler $commandHandler
    ) {}

    public function processExcel(ProcessExcelRequest $request): JsonResponse
    {
        try {
            $command = new ImportProductCommand(
                $request->file('file')->getPathname(),
                $request->input('lang')
            );

            $result = $this->commandHandler->handle($command);

            return response()->json([
                'success' => true,
                'message' => 'Excel file processed successfully',
                'data' => $result
            ], 200);

        } catch (ProductImportException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process Excel file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

#### Command Handler (25 lines)
```php
class ImportProductCommandHandler
{
    public function __construct(
        private ProductImportService $importService,
        private ProductValidationService $validationService
    ) {}

    public function handle(ImportProductCommand $command): array
    {
        // Validate file exists and is readable
        if (!file_exists($command->filePath)) {
            throw new ProductImportException("Excel file not found at: {$command->filePath}");
        }

        // Validate file format
        if (!$this->validationService->validateExcelFile($command->filePath)) {
            throw new ProductImportException("Invalid Excel file format");
        }

        // Process the file
        return $this->importService->processExcelFile($command->filePath, $command->language);
    }
}
```

#### Enhanced Service (Extracted from current ExcelImportService)
```php
class ProductImportService
{
    public function processExcelFile(string $filePath, string $language): array
    {
        // Load Excel file with error handling
        $spreadsheet = $this->loadExcelFile($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Validate minimum rows
        if (count($rows) < 2) {
            throw new ProductImportException('Excel file must contain at least a header row and one data row');
        }

        // Process data with transaction
        return DB::transaction(function () use ($rows, $language) {
            return $this->processRows($rows, $language);
        });
    }

    private function loadExcelFile(string $filePath): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        try {
            return \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new ProductImportException("Failed to load Excel file: {$e->getMessage()}");
        }
    }
}
```

## Example 3: Product Update with Complex Logic

### Before (Current ProductController - ~150 lines)
```php
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
        'attributes.*' => 'array',
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

        // ... (100+ more lines of similar logic for attributes, deliveries, documents, tags, itemTags)

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
```

### After (Refactored - ProductController)

#### Controller (25 lines)
```php
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private UpdateProductCommandHandler $commandHandler
    ) {}

    public function update(UpdateProductRequest $request, string $productCode): JsonResponse
    {
        try {
            $command = new UpdateProductCommand($productCode, $request->validated());
            $product = $this->commandHandler->handle($command);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => new ProductResource($product)
            ], 200);

        } catch (ProductNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (ProductValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->getErrors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

#### Command Handler (40 lines)
```php
class UpdateProductCommandHandler
{
    public function __construct(
        private ProductService $productService,
        private ProductValidationService $validationService
    ) {}

    public function handle(UpdateProductCommand $command): Product
    {
        // Validate product exists
        $product = $this->productService->findByCode($command->productCode);
        if (!$product) {
            throw new ProductNotFoundException("Product {$command->productCode} not found");
        }

        // Validate input data
        $errors = $this->validationService->validateProductUpdateData($command->data);
        if (!empty($errors)) {
            throw new ProductValidationException('Validation failed', $errors);
        }

        // Process update in transaction
        return DB::transaction(function () use ($product, $command) {
            // Update basic product fields
            $this->updateProductBasicFields($product, $command->data);

            // Update related entities
            $this->updateProductCategories($product, $command->data['categories'] ?? []);
            $this->updateProductAttributes($product, $command->data['attributes'] ?? []);
            $this->updateProductDeliveries($product, $command->data['deliveries'] ?? []);
            $this->updateProductDocuments($product, $command->data['documents'] ?? []);
            $this->updateProductTags($product, $command->data['tags'] ?? []);
            $this->updateProductItemTags($product, $command->data['itemTags'] ?? []);

            return $product->fresh();
        });
    }

    private function updateProductBasicFields(Product $product, array $data): void
    {
        $updateData = [];
        if (isset($data['isActive'])) {
            $updateData['is_active'] = $data['isActive'];
        }
        if (isset($data['cost'])) {
            $updateData['cost'] = $data['cost'];
        }
        if (isset($data['costCurrency'])) {
            $updateData['cost_currency'] = $data['costCurrency'];
        }
        if (isset($data['rrp'])) {
            $updateData['rrp'] = $data['rrp'];
        }
        if (isset($data['rrpCurrency'])) {
            $updateData['rrp_currency'] = $data['rrpCurrency'];
        }

        if (!empty($updateData)) {
            $product->update($updateData);
        }
    }
}
```

#### Service Methods (Extracted)
```php
class ProductService
{
    public function updateProductCategories(Product $product, array $categories): void
    {
        // Delete existing and recreate
        ProductCategory::where('product_code', $product->product_code)->delete();
        
        foreach ($categories as $categoryCode) {
            ProductCategory::create([
                'product_code' => $product->product_code,
                'category_code' => $categoryCode,
            ]);
        }
    }

    public function updateProductAttributes(Product $product, array $attributes): void
    {
        // Update attributes logic extracted from current controller
        foreach ($attributes as $language => $languageAttributes) {
            foreach ($languageAttributes as $attribute) {
                $existingAttribute = ProductAttributeValue::where('product_item_code', $product->product_code)
                    ->where('attribute_name', $attribute['name'])
                    ->where('language', $language)
                    ->first();

                if ($existingAttribute) {
                    $existingAttribute->update(['value' => $attribute['value']]);
                } else {
                    ProductAttributeValue::create([
                        'product_item_code' => $product->product_code,
                        'attribute_name' => $attribute['name'],
                        'language' => $language,
                        'value' => $attribute['value'],
                    ]);
                }
            }
        }
    }
}
```

## Benefits Demonstrated

### 1. **Reduced Complexity**
- Controller methods reduced from 150+ lines to 25 lines
- Clear separation of concerns
- Each class has a single responsibility

### 2. **Improved Testability**
- Each service method can be unit tested independently
- Command handlers can be tested without HTTP context
- Validation logic is centralized and testable

### 3. **Better Error Handling**
- Specific exceptions for different error types
- Consistent error response format
- Easier to handle errors at the appropriate level

### 4. **Enhanced Maintainability**
- Changes to image upload logic don't affect product update logic
- New import formats can be added without modifying existing code
- Validation rules are centralized and reusable

### 5. **Improved Reusability**
- Services can be used in different contexts (API, CLI, etc.)
- Commands can be queued for background processing
- Validation logic can be reused across different endpoints

## Migration Path

1. **Week 1**: Extract image upload logic to ProductImageService
2. **Week 2**: Extract Excel import logic to ProductImportService
3. **Week 3**: Extract product update logic to ProductService
4. **Week 4**: Create command handlers and update controllers
5. **Week 5**: Add comprehensive validation and error handling
6. **Week 6**: Update tests and documentation

This approach allows for gradual migration while maintaining backward compatibility and ensuring each step is thoroughly tested.