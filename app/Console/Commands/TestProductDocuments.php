<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Services\ProductService;

class TestProductDocuments extends Command
{
    protected $signature = 'test:product-documents';
    protected $description = 'Test product documents folder creation and API endpoint';

    public function handle()
    {
        $this->info('Testing product documents folder creation...');
        
        // Test 1: Check if product-documents directory exists
        $this->info('Test 1: Checking if product-documents directory exists...');
        if (!Storage::disk('public')->exists('product-documents')) {
            Storage::disk('public')->makeDirectory('product-documents');
            $this->info('✓ Created product-documents directory');
        } else {
            $this->info('✓ product-documents directory exists');
        }

        // Test 2: Create a test product
        $testProductCode = 'TEST_PRODUCT_' . time();
        $testLanguage = 'en';
        
        $this->info("Test 2: Creating test product: {$testProductCode}");
        
        // Create product
        $product = Product::create(['product_code' => $testProductCode]);
        
        // Create translation
        ProductTranslation::create([
            'product_code' => $testProductCode,
            'language' => $testLanguage,
            'title' => 'Test Product',
            'summary' => 'Test product for document testing',
            'description' => 'Test product for document testing'
        ]);
        
        $this->info("✓ Created test product: {$testProductCode}");

        // Test 3: Test folder creation for new product
        $this->info('Test 3: Testing folder creation for new product...');
        
        // Create product folder
        Storage::disk('public')->makeDirectory("product-documents/{$testProductCode}");
        
        // Create manual subfolder
        Storage::disk('public')->makeDirectory("product-documents/{$testProductCode}/manual");
        
        if (Storage::disk('public')->exists("product-documents/{$testProductCode}/manual")) {
            $this->info("✓ Folder created for product: {$testProductCode}");
        } else {
            $this->error("✗ Failed to create folder for product: {$testProductCode}");
            return 1;
        }

        // Test 4: Create some test documents
        $this->info('Test 4: Creating test documents...');
        
        $testFiles = [
            "product-documents/{$testProductCode}/manual/test_product_en.pdf",
            "product-documents/{$testProductCode}/manual/test_product_EN.pdf",
            "product-documents/{$testProductCode}/manual/test_product_lt.pdf",
            "product-documents/{$testProductCode}/manual/test_product_manual_de.pdf",
            "product-documents/{$testProductCode}/manual/test_product_specification_en.docx",
        ];
        
        foreach ($testFiles as $filePath) {
            Storage::disk('public')->put($filePath, "Test document content for {$testProductCode}");
        }
        
        $this->info('✓ Created test documents');

        // Test 5: Test the ProductService method
        $this->info('Test 5: Testing ProductService::getProductDocuments method...');
        
        $productService = app(ProductService::class);
        
        try {
            $documentsData = $productService->getProductDocuments($testProductCode, $testLanguage, 'manual');
            
            $this->info('✓ ProductService method executed successfully');
            $this->info('Documents found:');
            
            foreach ($documentsData['documents'] as $document) {
                $this->line("  - {$document['name']} ({$document['file_size_mb']} MB, {$document['file_type']})");
            }
            
        } catch (\Exception $e) {
            $this->error('✗ ProductService method failed: ' . $e->getMessage());
            return 1;
        }

        // Test 6: Test with different purpose
        $this->info('Test 6: Testing with different purpose (installation)...');
        
        // Create installation folder and documents
        Storage::disk('public')->makeDirectory("product-documents/{$testProductCode}/installation");
        Storage::disk('public')->put("product-documents/{$testProductCode}/installation/installation_guide_en.pdf", "Installation guide");
        
        try {
            $installationData = $productService->getProductDocuments($testProductCode, $testLanguage, 'installation');
            
            $this->info('✓ Installation documents retrieved successfully');
            $this->info("Found " . count($installationData['documents']) . " documents for installation purpose");
            
        } catch (\Exception $e) {
            $this->error('✗ Installation test failed: ' . $e->getMessage());
            return 1;
        }

        // Test 7: Test with non-existent product
        $this->info('Test 7: Testing with non-existent product...');
        
        try {
            $nonExistentData = $productService->getProductDocuments('NON_EXISTENT_PRODUCT', $testLanguage, 'manual');
            
            if (empty($nonExistentData['documents'])) {
                $this->info('✓ Correctly handled non-existent product');
            } else {
                $this->error('✗ Should not return documents for non-existent product');
                return 1;
            }
            
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                $this->info('✓ Correctly threw exception for non-existent product');
            } else {
                $this->error('✗ Unexpected error: ' . $e->getMessage());
                return 1;
            }
        }

        // Test 8: Test with non-existent folder
        $this->info('Test 8: Testing with non-existent folder...');
        
        $testProductCode2 = 'TEST_PRODUCT_NO_DOCS_' . time();
        $product2 = Product::create(['product_code' => $testProductCode2]);
        ProductTranslation::create([
            'product_code' => $testProductCode2,
            'language' => $testLanguage,
            'title' => 'Test Product No Docs',
            'summary' => 'Test product without documents',
            'description' => 'Test product without documents'
        ]);
        
        try {
            $noDocsData = $productService->getProductDocuments($testProductCode2, $testLanguage, 'manual');
            
            if (empty($noDocsData['documents'])) {
                $this->info('✓ Correctly handled non-existent folder');
            } else {
                $this->error('✗ Should not return documents when folder does not exist');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('✗ Unexpected error: ' . $e->getMessage());
            return 1;
        }

        // Cleanup
        $this->info('Cleaning up test products...');
        
        // Delete test products and their translations
        ProductTranslation::where('product_code', $testProductCode)->delete();
        Product::where('product_code', $testProductCode)->delete();
        
        ProductTranslation::where('product_code', $testProductCode2)->delete();
        Product::where('product_code', $testProductCode2)->delete();
        
        // Delete test folders
        Storage::disk('public')->deleteDirectory("product-documents/{$testProductCode}");
        Storage::disk('public')->deleteDirectory("product-documents/{$testProductCode2}");
        
        $this->info('✓ Test products and folders cleaned up');

        $this->info('');
        $this->info('All tests passed! ✓');
        $this->info('');
        $this->info('API Endpoint Usage:');
        $this->info('GET /api/products/{productCode}/documents?lang=en&purpose=manual');
        $this->info('');
        $this->info('Example:');
        $this->info('GET /api/products/PRODUCT123/documents?lang=en&purpose=manual');
        $this->info('');
        $this->info('Response format:');
        $this->info('{');
        $this->info('  "success": true,');
        $this->info('  "message": "Product documents retrieved successfully",');
        $this->info('  "data": {');
        $this->info('    "product_code": "PRODUCT123",');
        $this->info('    "language": "en",');
        $this->info('    "purpose": "manual",');
        $this->info('    "documents": [');
        $this->info('      {');
        $this->info('        "file_path": "http://localhost/storage/product-documents/PRODUCT123/manual/product123_manual_en.pdf",');
        $this->info('        "file_size_mb": 1.5,');
        $this->info('        "file_type": "pdf",');
        $this->info('        "name": "Test Product - manual.pdf",');
        $this->info('        "original_filename": "product123_manual_en.pdf"');
        $this->info('      }');
        $this->info('    ]');
        $this->info('  }');
        $this->info('}');

        return 0;
    }
}