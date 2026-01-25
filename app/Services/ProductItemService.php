<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\ProductItemTranslation;
use Illuminate\Support\Facades\DB;

class ProductItemService
{
    /**
     * Create a new product item
     */
    public function createProductItem(array $data): ProductItem
    {
        DB::beginTransaction();

        try {
            $productItem = ProductItem::create([
                'product_item_code' => $data['productItemCode'],
                'isku' => $data['isku'],
                'product_code' => $data['productCode'],
                'is_active' => $data['isActive'] ?? true,
                'cost' => $data['cost'] ?? null,
                'cost_currency' => $data['costCurrency'] ?? 'EUR',
                'rrp' => $data['rrp'] ?? null,
                'rrp_currency' => $data['rrpCurrency'] ?? 'EUR',
                'availability' => $data['availability'] ?? 'o',
            ]);

            // Create translations
            if (!empty($data['translations'])) {
                foreach ($data['translations'] as $translationData) {
                    ProductItemTranslation::create([
                        'isku' => $productItem->isku,
                        'language' => $translationData['language'],
                        'title' => $translationData['title'],
                        'short_desc' => $translationData['short_desc'] ?? null,
                        'variation_text' => $translationData['variation_text'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return $productItem->load('translations');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update product item
     */
    public function updateProductItem(string $isku, array $data): ProductItem
    {
        $productItem = $this->findByIsku($isku);

        if (!$productItem) {
            throw new \Exception('Product item not found');
        }

        DB::beginTransaction();

        try {
            $updateData = [];
            if (isset($data['productItemCode'])) {
                $updateData['product_item_code'] = $data['productItemCode'];
            }
            if (isset($data['productCode'])) {
                $updateData['product_code'] = $data['productCode'];
            }
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
            if (isset($data['availability'])) {
                $updateData['availability'] = $data['availability'];
            }

            if (!empty($updateData)) {
                $productItem->update($updateData);
            }

            DB::commit();

            return $productItem->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Find product item by ISKU
     */
    public function findByIsku(string $isku): ?ProductItem
    {
        return ProductItem::where('isku', $isku)->first();
    }

    /**
     * Get product items by product code
     */
    public function getByProductCode(string $productCode, string $language = 'en', int $page = 1, int $perPage = 20): array
    {
        $query = ProductItem::with([
            'translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }
        ])->where('product_code', $productCode);

        $paginatedItems = $query->paginate($perPage, ['*'], 'page', $page);

        $response = [];
        foreach ($paginatedItems->items() as $productItem) {
            $response[] = [
                'item_code' => $productItem->product_item_code,
                'isku' => $productItem->isku,
                'title' => $productItem->translations->where('language', $language)->first()?->title ?? $productItem->product_item_code,
                'cost' => $productItem->cost,
                'rrp' => $productItem->rrp,
                'cost_currency' => $productItem->cost_currency,
                'rrp_currency' => $productItem->rrp_currency,
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
     * Get product code by ISKU
     */
    public function getProductCodeByIsku(string $isku): ?string
    {
        $productItem = $this->findByIsku($isku);
        return $productItem ? $productItem->product_code : null;
    }
}
