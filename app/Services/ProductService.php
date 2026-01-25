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
}
