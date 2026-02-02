<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * Create a new product with translations
     */
    public function createProduct(array $data): Product
    {
        DB::beginTransaction();

        try {
            $product = Product::create([
                'product_code' => $data['productCode'],
            ]);

            // Create product image directory structure
            Storage::disk('public')->makeDirectory($data['productCode']);

            // Create translations
            foreach ($data['translations'] as $translationData) {
                ProductTranslation::create([
                    'product_code' => $product->product_code,
                    'language' => $translationData['language'],
                    'title' => $translationData['title'],
                    'summary' => $translationData['summary'] ?? null,
                    'description' => $translationData['description'] ?? null,
                ]);
            }

            DB::commit();

            return $product->load('translations');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Find product by code
     */
    public function findByCode(string $productCode): ?Product
    {
        return Product::where('product_code', $productCode)->first();
    }

    /**
     * Update product
     */
    public function updateProduct(string $productCode, array $data): Product
    {
        $product = $this->findByCode($productCode);

        if (!$product) {
            throw new \Exception('Product not found');
        }

        DB::beginTransaction();

        try {
            // Update basic product fields
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

            DB::commit();

            return $product->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get product documents for a specific product and language
     */
    public function getProductDocuments(string $productCode, string $language, ?string $purpose = 'manual'): array
    {
        // Default to 'manual' if purpose is empty
        $purpose = $purpose ?? 'manual';
        
        // Get product name in the requested language for file naming
        $productName = DB::table('product_translation')
            ->where('product_code', $productCode)
            ->where('language', $language)
            ->value('title');

        if (!$productName) {
            throw new \Exception('Product not found or no translation available for the specified language', 404);
        }

        // Determine the folder path based on purpose
        $folderPath = "product-documents/{$productCode}/{$purpose}";
        
        // Check if the folder exists
        if (!Storage::disk('public')->exists($folderPath)) {
            return [
                'product_code' => $productCode,
                'language' => $language,
                'purpose' => $purpose,
                'documents' => []
            ];
        }

        // Get all files in the folder
        $files = Storage::disk('public')->files($folderPath);
        
        // Filter files by language (case-insensitive, exact match)
        $languageCode = strtolower($language);
        $matchingFiles = array_filter($files, function ($file) use ($languageCode) {
            $filename = strtolower(basename($file));
            // Check if filename contains the language code as a separate word or at the end
            return preg_match('/\b' . preg_quote($languageCode, '/') . '\b/', $filename);
        });

        $documents = [];
        
        foreach ($matchingFiles as $filePath) {
            $fileName = basename($filePath);
            $fileSize = Storage::disk('public')->size($filePath);
            $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
            
            // Format file size in MB
            $fileSizeMB = round($fileSize / (1024 * 1024), 2);
            
            // Create display name with product name and purpose (capitalized, without extension, no dash)
            $displayName = ucwords("{$productName} {$purpose}");

            // Generate full absolute URL for direct browser download
            $baseUrl = config('app.url');
            $fileUrl = $baseUrl . '/storage/' . $filePath;
            
            $documents[] = [
                'file_path' => $fileUrl,
                'file_size_mb' => $fileSizeMB,
                'file_type' => $fileType,
                'name' => $displayName,
                'original_filename' => $fileName
            ];
        }

        return [
            'product_code' => $productCode,
            'language' => $language,
            'purpose' => $purpose,
            'documents' => $documents
        ];
    }
}
