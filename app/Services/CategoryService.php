<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    /**
     * Get category details for an array of category codes
     */
    public function getCategories(array $categoryCodes, string $language): array
    {
        $categories = $this->categoryRepository->getWithRelationships($categoryCodes, $language);

        return $categories->map(function ($category) use ($language) {
            return [
                'category_code' => $category->category_code,
                'parent_codes' => $category->parents->pluck('category_code')->toArray(),
                'name' => $category->translations->first()?->name ?? null,
                'image_url' => $this->getCategoryImageUrl($category->category_code, $language),
            ];
        })->toArray();
    }

    /**
     * Get the complete category tree
     */
    public function getCategoryTree(string $language): array
    {
        $cacheKey = "category_tree_{$language}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function() use ($language) {
            // Use the optimized single query method
            $categoriesData = $this->categoryRepository->getCategoryTreeData($language);
            return $this->buildOptimizedCategoryTree($categoriesData, $language);
        });
    }

    /**
     * Get sub-categories for a given category code
     */
    public function getSubCategories(string $categoryCode, string $language): array
    {
        $subCategories = $this->categoryRepository->getSubCategories($categoryCode, $language);

        $response = [];
        foreach ($subCategories as $subCategory) {
            $response[] = [
                'name' => $subCategory->name ?? $subCategory->category_code,
                'code' => $subCategory->category_code,
                'has_sub_categories' => $this->categoryRepository->hasSubCategories($subCategory->category_code),
                'image_url' => $this->getCategoryImageUrl($subCategory->category_code, $language),
            ];
        }

        return $response;
    }

    /**
     * Get category details for a list of category codes
     */
    public function getCategoryDetails(array $categoryCodes, string $language): array
    {
        $categories = $this->categoryRepository->getDetailsWithTranslations($categoryCodes, $language);

        $response = [];
        foreach ($categories as $category) {
            $response[] = [
                'code' => $category->category_code,
                'title' => $category->title ?? $category->category_code,
                'image' => $this->getCategoryImageUrl($category->category_code, $language)
            ];
        }

        return $response;
    }

    /**
     * Build a hierarchical tree structure from flat category data
     */
    private function buildCategoryTree($categories, $language): array
    {
        $hierarchy = $this->categoryRepository->getHierarchy();

        $categoryMap = [];
        foreach ($categories as $category) {
            $translation = $category->translations->first();

            $parentCodes = isset($hierarchy[$category->category_code]) ?
                collect($hierarchy[$category->category_code])->pluck('parent_code')->toArray() : [];

            $categoryMap[$category->category_code] = [
                'category_code' => $category->category_code,
                'name' => $translation ? $translation->name : null,
                'image_url' => $this->getCategoryImageUrl($category->category_code, $language),
                'parent_codes' => $parentCodes,
                'children' => []
            ];
        }

        $tree = [];
        foreach ($categoryMap as $code => &$category) {
            if (empty($category['parent_codes'])) {
                $tree[] = &$category;
            } else {
                foreach ($category['parent_codes'] as $parentCode) {
                    if (isset($categoryMap[$parentCode])) {
                        $categoryMap[$parentCode]['children'][] = &$category;
                    }
                }
            }
        }

        return $tree;
    }

    /**
     * Build an optimized hierarchical tree structure from flat category data
     * This method uses the optimized single query result to eliminate N+1 queries
     */
    private function buildOptimizedCategoryTree(array $categoriesData, string $language): array
    {
        $categoryMap = [];

        // Process the optimized query result
        foreach ($categoriesData as $categoryData) {
            $categoryCode = $categoryData->category_code;
            $name = $categoryData->name;
            $parentCodes = $categoryData->parent_codes ? explode(',', $categoryData->parent_codes) : [];

            $categoryMap[$categoryCode] = [
                'category_code' => $categoryCode,
                'name' => $name,
                'image_url' => $this->getCachedCategoryImageUrl($categoryCode, $language),
                'parent_codes' => $parentCodes,
                'children' => []
            ];
        }

        // Build the tree structure
        $tree = [];
        foreach ($categoryMap as $code => &$category) {
            if (empty($category['parent_codes'])) {
                $tree[] = &$category;
            } else {
                foreach ($category['parent_codes'] as $parentCode) {
                    if (isset($categoryMap[$parentCode])) {
                        $categoryMap[$parentCode]['children'][] = &$category;
                    }
                }
            }
        }

        return $tree;
    }

    /**
     * Get category image URL
     */
    private function getCategoryImageUrl(string $categoryCode, string $language): ?string
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

        // Category images are stored as: categories/{Category_code}.{extension}
        // No language prefix as specified
        $imagePath = "categories/{$categoryCode}";

        foreach ($imageExtensions as $ext) {
            $fullImagePath = "{$imagePath}.{$ext}";
            if (Storage::disk('public')->exists($fullImagePath)) {
                return asset("storage/{$fullImagePath}");
            }
        }

        return null;
    }

    /**
     * Get cached category image URL to reduce file system operations
     */
    private function getCachedCategoryImageUrl(string $categoryCode, string $language): ?string
    {
        $cacheKey = "category_image_{$categoryCode}_{$language}";
        
        return Cache::remember($cacheKey, now()->addHours(2), function() use ($categoryCode, $language) {
            return $this->getCategoryImageUrl($categoryCode, $language);
        });
    }
}
