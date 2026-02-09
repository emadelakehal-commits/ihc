# Product Controller Refactoring Plan

## Current State Analysis

The `ProductController` is over 2000 lines of code and violates several SOLID principles:

### Issues Identified:
1. **Single Responsibility Violation**: Controller handles product CRUD, image management, Excel import, related products, categories, tags, etc.
2. **God Object Pattern**: One controller doing everything
3. **Mixed Concerns**: Business logic mixed with HTTP concerns
4. **Code Duplication**: Similar logic repeated across methods
5. **Poor Testability**: Hard to unit test due to tight coupling
6. **Maintenance Nightmare**: Changes in one area can break unrelated functionality

## Refactoring Strategy

### Design Patterns to Apply:

#### 1. Service Layer Pattern
**Purpose**: Extract business logic from controllers
**Implementation**:
- Create specialized service classes for each domain
- Move complex business logic from controller to services
- Keep controllers thin and focused on HTTP concerns

#### 2. Repository Pattern
**Purpose**: Abstract data access logic
**Implementation**:
- Create repository interfaces and implementations
- Centralize database queries and data access
- Enable easier testing and database abstraction

#### 3. Command Pattern
**Purpose**: Encapsulate operations as objects
**Implementation**:
- Create command classes for complex operations
- Enable undo/redo, logging, and validation
- Make operations composable and testable

#### 4. Factory Pattern
**Purpose**: Handle object creation complexity
**Implementation**:
- Create factories for complex object creation
- Centralize validation and transformation logic
- Reduce duplication in object creation

#### 5. Strategy Pattern
**Purpose**: Encapsulate algorithms and make them interchangeable
**Implementation**:
- Create strategies for different import/export formats
- Allow runtime selection of algorithms
- Improve maintainability and testability

## Proposed Architecture

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── ProductController.php (Refactored - ~200 lines)
│           ├── ProductImageController.php (NEW)
│           ├── ProductImportController.php (NEW)
│           ├── ProductRelationController.php (NEW)
│           └── ProductCategoryController.php (NEW)
├── Services/
│   ├── ProductService.php (Enhanced)
│   ├── ProductImageService.php (Enhanced)
│   ├── ProductImportService.php (Enhanced)
│   ├── ProductRelationService.php (NEW)
│   ├── ProductCategoryService.php (NEW)
│   └── ProductValidationService.php (NEW)
├── Repositories/
│   ├── ProductRepository.php (NEW)
│   ├── ProductItemRepository.php (NEW)
│   ├── ProductImageRepository.php (NEW)
│   └── ProductRelationRepository.php (NEW)
├── Commands/
│   ├── CreateProductCommand.php (NEW)
│   ├── UpdateProductCommand.php (NEW)
│   ├── ImportProductCommand.php (NEW)
│   └── ProcessProductImagesCommand.php (NEW)
├── Factories/
│   ├── ProductFactory.php (NEW)
│   ├── ProductItemFactory.php (NEW)
│   └── ProductImportFactory.php (NEW)
└── Strategies/
    ├── ExcelImportStrategy.php (NEW)
    ├── CsvImportStrategy.php (NEW)
    └── JsonImportStrategy.php (NEW)
```

## Detailed Refactoring Plan

### Phase 1: Extract Service Layer (Week 1)

#### 1.1 Create ProductImageService
**Current Methods to Extract**:
- `uploadProductImages()`
- `uploadProductItemImages()`
- `downloadProductImages()`

**New Service Methods**:
```php
class ProductImageService {
    public function uploadProductImages(string $productCode, array $images): array
    public function uploadProductItemImages(string $productItemCode, array $images): array
    public function downloadProductImages(string $driveUrl): array
    public function organizeProductImages(string $tempDir, string $productCode): array
    public function validateImageFiles(array $files): array
}
```

#### 1.2 Create ProductImportService
**Current Methods to Extract**:
- `processExcel()`
- `convertToMm()`
- `parseCategories()`
- `parseTags()`
- `parseRelatedProducts()`
- `parseAvailability()`

**New Service Methods**:
```php
class ProductImportService {
    public function processExcelFile(string $filePath, string $language): array
    public function validateExcelFile(string $filePath): bool
    public function parseProductData(array $row, array $columnMapping): array
    public function createOrUpdateProduct(array $data): Product
    public function createOrUpdateProductItem(array $data): ProductItem
}
```

#### 1.3 Create ProductRelationService
**Current Methods to Extract**:
- `getRelatedProducts()`
- `getProductCodeByIsku()`

**New Service Methods**:
```php
class ProductRelationService {
    public function getRelatedProducts(string $entityCode, string $language): array
    public function getProductCodeByIsku(string $isku): string
    public function createRelatedProduct(string $fromEntity, string $toEntity, string $relationType): bool
    public function removeRelatedProduct(string $fromEntity, string $toEntity): bool
}
```

### Phase 2: Create Repository Layer (Week 2)

#### 2.1 Create ProductRepository
```php
interface ProductRepositoryInterface {
    public function findByCode(string $productCode): ?Product
    public function findByCodes(array $productCodes): Collection
    public function findByCategories(array $categoryCodes): Collection
    public function findByTags(array $tagCodes): Collection
    public function searchProducts(string $searchTerm): Collection
    public function create(array $data): Product
    public function update(string $productCode, array $data): bool
    public function delete(string $productCode): bool
}
```

#### 2.2 Create ProductItemRepository
```php
interface ProductItemRepositoryInterface {
    public function findByIsku(string $isku): ?ProductItem
    public function findByProductCode(string $productCode): Collection
    public function findByCategories(array $categoryCodes): Collection
    public function findByTags(array $tagCodes): Collection
    public function create(array $data): ProductItem
    public function update(string $isku, array $data): bool
    public function delete(string $isku): bool
}
```

### Phase 3: Implement Command Pattern (Week 3)

#### 3.1 Create Product Commands
```php
class CreateProductCommand {
    public function __construct(
        public string $productCode,
        public array $translations,
        public array $categories = [],
        public array $tags = []
    ) {}
}

class UpdateProductCommand {
    public function __construct(
        public string $productCode,
        public array $data
    ) {}
}

class ImportProductCommand {
    public function __construct(
        public string $filePath,
        public string $language
    ) {}
}
```

#### 3.2 Create Command Handlers
```php
class CreateProductCommandHandler {
    public function handle(CreateProductCommand $command): Product
}

class UpdateProductCommandHandler {
    public function handle(UpdateProductCommand $command): Product
}

class ImportProductCommandHandler {
    public function handle(ImportProductCommand $command): array
}
```

### Phase 4: Refactor Controller (Week 4)

#### 4.1 Simplified ProductController
```php
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private ProductRepositoryInterface $productRepository
    ) {}

    public function show(string $productCode): JsonResponse
    {
        $product = $this->productService->getProductWithDetails($productCode);
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, string $productCode): JsonResponse
    {
        $command = new UpdateProductCommand($productCode, $request->validated());
        $product = $this->productService->updateProduct($command);
        return new ProductResource($product);
    }

    public function import(ImportProductRequest $request): JsonResponse
    {
        $command = new ImportProductCommand(
            $request->file('file')->getPathname(),
            $request->input('lang')
        );
        $result = $this->productService->importProducts($command);
        return response()->json(['data' => $result]);
    }
}
```

#### 4.2 New Specialized Controllers
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

class ProductImportController extends Controller
{
    public function __construct(private ProductImportService $importService) {}

    public function processExcel(ProcessExcelRequest $request): JsonResponse
    {
        $result = $this->importService->processExcelFile(
            $request->file('file')->getPathname(),
            $request->input('lang')
        );
        return response()->json(['data' => $result]);
    }
}
```

### Phase 5: Add Validation and Error Handling (Week 5)

#### 5.1 Create Validation Service
```php
class ProductValidationService {
    public function validateProductData(array $data): array
    public function validateProductItemData(array $data): array
    public function validateImportData(array $data): array
    public function validateImageFiles(array $files): array
}
```

#### 5.2 Create Error Handling
```php
class ProductValidationException extends Exception {
    public function __construct(string $message, array $errors = []) {}
}

class ProductNotFoundException extends Exception {}
class ProductImportException extends Exception {}
```

## Benefits of This Refactoring

### 1. **Improved Maintainability**
- Each class has a single, clear responsibility
- Changes in one area don't affect others
- Easier to understand and modify code

### 2. **Better Testability**
- Each service can be unit tested independently
- Mock dependencies easily in tests
- Clear separation between business logic and HTTP concerns

### 3. **Enhanced Reusability**
- Services can be reused across different controllers
- Commands can be used in different contexts (API, CLI, etc.)
- Repository pattern enables database abstraction

### 4. **Improved Performance**
- Lazy loading of related data
- Optimized database queries in repositories
- Better caching strategies

### 5. **Better Error Handling**
- Centralized error handling
- Consistent error responses
- Better logging and debugging

## Implementation Timeline

| Week | Phase | Tasks |
|------|-------|-------|
| 1 | Service Layer | Extract image, import, and relation services |
| 2 | Repository Layer | Create repository interfaces and implementations |
| 3 | Command Pattern | Implement command classes and handlers |
| 4 | Controller Refactoring | Simplify controllers and create specialized ones |
| 5 | Validation & Testing | Add validation service and comprehensive tests |
| 6 | Documentation | Update API documentation and code comments |

## Migration Strategy

### 1. **Backward Compatibility**
- Keep existing API endpoints working during transition
- Use feature flags to enable new code gradually
- Maintain database schema compatibility

### 2. **Gradual Migration**
- Migrate one controller method at a time
- Test each migration step thoroughly
- Rollback capability for each step

### 3. **Testing Strategy**
- Unit tests for each service and repository
- Integration tests for controller endpoints
- End-to-end tests for complete workflows

## Code Quality Improvements

### 1. **Naming Conventions**
- Use descriptive class and method names
- Follow PSR standards
- Consistent naming across the application

### 2. **Code Organization**
- Group related functionality together
- Use proper directory structure
- Clear separation of concerns

### 3. **Documentation**
- PHPDoc comments for all public methods
- API documentation updates
- Code comments for complex logic

## Conclusion

This refactoring plan will transform the 2000+ line monolithic controller into a well-structured, maintainable, and testable codebase. The application will be easier to understand, modify, and extend while maintaining all existing functionality.

The phased approach ensures minimal disruption to ongoing development while providing immediate benefits in code quality and maintainability.