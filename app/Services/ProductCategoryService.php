<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductItemTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ImageService;

class ProductCategoryService
{
    /**
     * @var ImageService
     */
    private $imageService;

    /**
     * ProductCategoryService constructor.
     */
    public function __construct()
    {
        $this->imageService = app(ImageService::class);
    }

    /**
     * Get product items by category codes, or products if no items exist.
     */
    public function getProductsByCategories(array $categoryCodes, string $language, int $page = 1, int $perPage = 20): array
    {
        try {
            // First, check if there are any product items for the given categories
            $hasProductItems = DB::table('product_category')
                ->whereIn('category_code', $categoryCodes)
                ->join('product_item', 'product_category.product_code', '=', 'product_item.product_code')
                ->exists();

            if ($hasProductItems) {
                return $this->getProductItemsByCategories($categoryCodes, $language, $page, $perPage);
            } else {
                return $this->getProductsByCategoriesOnly($categoryCodes, $language, $page, $perPage);
            }

        } catch (\Exception $e) {
            Log::error('Error getting products by categories', [
                'categoryCodes' => $categoryCodes,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error getting products by categories: ' . $e->getMessage());
        }
    }

    /**
     * Get product items by category codes with pagination.
     */
    private function getProductItemsByCategories(array $categoryCodes, string $language, int $page, int $perPage): array
    {
        $query = DB::table('product_category')
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

        // Apply pagination
        $paginatedItems = $query->paginate($perPage, ['*'], 'page', $page);

        $response = [];
        foreach ($paginatedItems->items() as $item) {
            // Get product item image using ImageService (placeholder)
            $imageUrl = $this->getProductItemImageUrlForCategories($item->product_code, $item->isku);

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
     * Get products by category codes with pagination (when no product items exist).
     */
    private function getProductsByCategoriesOnly(array $categoryCodes, string $language, int $page, int $perPage): array
    {
        $query = DB::table('product_category')
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

        // Apply pagination
        $paginatedProducts = $query->paginate($perPage, ['*'], 'page', $page);

        $response = [];
        foreach ($paginatedProducts->items() as $product) {
            // Get product image using ImageService (placeholder)
            $imageUrl = $this->getProductImageUrlForCategories($product->product_code);

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

        return [
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
        ];
    }

    /**
     * Get product image URL for categories.
     */
    private function getProductImageUrlForCategories(string $productCode): ?string
    {
        return $this->imageService->getProductImageUrlForCategories($productCode);
    }

    /**
     * Get product item image URL for categories.
     */
    private function getProductItemImageUrlForCategories(string $productCode, string $isku): ?string
    {
        return $this->imageService->getProductItemImageUrlForCategories($productCode, $isku);
    }
}