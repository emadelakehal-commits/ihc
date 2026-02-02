# Product Documents Implementation

This document provides a comprehensive overview of the product documents feature implementation for the IHC Laravel application.

## Overview

The product documents feature allows for automatic folder creation and document management for products. When new products are created (either via Excel import or API), the system automatically creates a dedicated folder structure for storing product documents.

## Folder Structure

```
storage/app/public/product-documents/
├── PRODUCT123/
│   ├── manual/
│   │   ├── product123_manual_en.pdf
│   │   ├── product123_manual_de.pdf
│   │   └── product123_specification_en.docx
│   ├── installation/
│   │   └── product123_installation_guide_en.pdf
│   └── warranty/
│       └── product123_warranty_en.pdf
└── PRODUCT456/
    └── manual/
        └── product456_manual_en.pdf
```

## Features Implemented

### 1. Automatic Folder Creation

- **On Deployment**: Creates `product-documents` directory in `storage/app/public/`
- **On Product Creation**: Creates product-specific folder when new products are created
- **Manual Subfolder**: Creates `manual` subfolder for each product by default

### 2. Excel Import Integration

- **New Products**: Automatically creates folders for products created via Excel import
- **Existing Products**: Skips folder creation if product already exists
- **Error Handling**: Graceful handling of folder creation failures

### 3. API Endpoint

**Endpoint**: `GET /api/products/{productCode}/documents`

**Parameters**:
- `lang` (required): Language code (e.g., 'en', 'de', 'lt')
- `purpose` (optional): Document purpose (e.g., 'manual', 'installation', 'warranty')

**Example**: `GET /api/products/PRODUCT123/documents?lang=en&purpose=manual`

### 4. Response Format

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Product documents retrieved successfully",
  "data": {
    "product_code": "PRODUCT123",
    "language": "en",
    "purpose": "manual",
    "documents": [
      {
        "file_path": "http://localhost/storage/product-documents/PRODUCT123/manual/product123_manual_en.pdf",
        "file_size_mb": 1.5,
        "file_type": "pdf",
        "name": "Product123 Manual",
        "original_filename": "product123_manual_en.pdf"
      }
    ]
  }
}
```

#### Error Response (404 Not Found)

```json
{
  "success": false,
  "message": "Product not found"
}
```

#### Error Response (422 Unprocessable Entity)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "product_code": ["The product code field is required."],
    "lang": ["The lang field is required."]
  }
}
```

#### Error Response (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Failed to retrieve product documents",
  "error": "Detailed error message"
}
```

### Response Structure Details

#### Root Level
- **`success`** (boolean): Indicates if the request was successful
- **`message`** (string): Human-readable message about the result
- **`data`** (object, optional): Contains the actual data when successful

#### Data Object
- **`product_code`** (string): The product code requested
- **`language`** (string): The language code used for filtering
- **`purpose`** (string): The document purpose (manual, installation, warranty)
- **`documents`** (array): Array of document objects

#### Document Object
- **`file_path`** (string): Full URL to access the document
- **`file_size_mb`** (number): File size in megabytes (rounded to 2 decimal places)
- **`file_type`** (string): File extension (pdf, docx, etc.)
- **`name`** (string): Display name for the document (e.g., "Product Name Manual")
- **`original_filename`** (string): Original filename from the filesystem

## Files Created/Modified

### New Files

1. **`app/Http/Requests/GetProductDocumentsRequest.php`**
   - Request validation for the product documents endpoint
   - Validates product code, language, and purpose parameters

2. **`app/Http/Resources/ProductDocumentsResource.php`**
   - Resource class for formatting the API response
   - Transforms document data into the required format

3. **`app/Services/ProductService.php`** (modified)
   - Added `getProductDocuments()` method
   - Handles document retrieval and processing logic

4. **`app/Http/Controllers/Api/ProductController.php`** (modified)
   - Added `getProductDocuments()` endpoint method
   - Integrates with ProductService for document retrieval

5. **`routes/api.php`** (modified)
   - Added route for the new endpoint

6. **`app/Console/Commands/TestProductDocuments.php`**
   - Test command to verify the implementation
   - Tests all functionality including edge cases

### Modified Files

1. **`app/Services/ExcelImportService.php`**
   - Added automatic folder creation for new products
   - Creates product folder and manual subfolder

2. **`app/Console/Commands/SetupIhcDatabase.php`**
   - Added product-documents directory creation on deployment

## Implementation Details

### Folder Creation Logic

1. **Product Folder**: Created as `product-documents/{productCode}`
2. **Purpose Subfolders**: Created as `product-documents/{productCode}/{purpose}`
3. **Default Manual Folder**: Always created for new products
4. **Error Handling**: Graceful handling of existing folders and creation failures

### Document Processing

1. **File Discovery**: Scans the specified purpose folder for files
2. **Language Filtering**: Filters files by language code in filename
3. **File Information**: Extracts file size, type, and generates display names
4. **URL Generation**: Creates public URLs for document access

### Security Considerations

- **Path Validation**: Validates product codes to prevent directory traversal
- **File Access**: Only serves files from the designated product-documents directory
- **Input Validation**: Validates all request parameters

## Testing

### Test Command Usage

```bash
php artisan test:product-documents
```

### Test Coverage

1. ✅ Folder creation for new products
2. ✅ Manual subfolder creation
3. ✅ Document file creation and retrieval
4. ✅ Different purpose folders (manual, installation)
5. ✅ Non-existent product handling
6. ✅ Non-existent folder handling
7. ✅ API endpoint functionality
8. ✅ Error handling and edge cases

## Usage Examples

### Creating a New Product with Documents

```php
// Via Excel Import
// Folders are automatically created for new products

// Via API
POST /api/products
{
  "product_code": "NEW_PRODUCT_123",
  "translations": [
    {
      "language": "en",
      "title": "New Product",
      "summary": "Product summary",
      "description": "Product description"
    }
  ]
}

// Folders are automatically created:
// - storage/app/public/product-documents/NEW_PRODUCT_123/
// - storage/app/public/product-documents/NEW_PRODUCT_123/manual/
```

### Retrieving Product Documents

```bash
# Get manual documents for English
GET /api/products/PRODUCT123/documents?lang=en&purpose=manual

# Get installation documents for German
GET /api/products/PRODUCT123/documents?lang=de&purpose=installation

# Get warranty documents (default purpose is manual)
GET /api/products/PRODUCT123/documents?lang=en
```

### Adding Documents

Documents can be added to the appropriate folders:

```bash
# Add manual document
storage/app/public/product-documents/PRODUCT123/manual/product123_manual_en.pdf

# Add installation document
storage/app/public/product-documents/PRODUCT123/installation/product123_installation_de.pdf

# Add warranty document
storage/app/public/product-documents/PRODUCT123/warranty/product123_warranty_lt.pdf
```

## Future Enhancements

1. **Document Upload API**: Allow uploading documents via API
2. **Document Management**: CRUD operations for documents
3. **Document Categories**: More granular document categorization
4. **Document Versioning**: Support for multiple versions of documents
5. **Document Permissions**: Access control for sensitive documents
6. **Document Search**: Search functionality across all documents

## Troubleshooting

### Common Issues

1. **Folder Not Created**: Check storage permissions and disk configuration
2. **Documents Not Found**: Verify file naming conventions and language codes
3. **API Errors**: Check request parameters and product existence
4. **File Access Issues**: Ensure files are in the correct directory structure

### Debug Commands

```bash
# Check if product-documents directory exists
ls storage/app/public/product-documents/

# Check specific product folder
ls storage/app/public/product-documents/PRODUCT123/

# Run test command
php artisan test:product-documents

# Check storage disk configuration
php artisan storage:link
```

## Conclusion

The product documents feature provides a robust and scalable solution for managing product documentation. The automatic folder creation ensures consistency, while the API endpoint provides easy access to documents. The implementation includes comprehensive error handling and testing to ensure reliability.