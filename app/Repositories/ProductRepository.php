<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    /**
     * Find product by code
     */
    public function findByCode(string $productCode): ?Product
    {
        return Product::where('product_code', $productCode)->first();
    }

    /**
     * Get products by codes
     */
    public function getByCodes(array $productCodes): Collection
    {
        return Product::whereIn('product_code', $productCodes)->get();
    }

    /**
     * Create a new product
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update product
     */
    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    /**
     * Delete product
     */
    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Get products with translations
     */
    public function getWithTranslations(array $productCodes, string $language): Collection
    {
        return Product::with([
            'translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }
        ])->whereIn('product_code', $productCodes)->get();
    }
}
