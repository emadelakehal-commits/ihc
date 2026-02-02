<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    /**
     * Find category by code
     */
    public function findByCode(string $categoryCode): ?Category
    {
        return Category::where('category_code', $categoryCode)->first();
    }

    /**
     * Get categories by codes
     */
    public function getByCodes(array $categoryCodes): Collection
    {
        return Category::whereIn('category_code', $categoryCodes)->get();
    }

    /**
     * Get categories with relationships
     */
    public function getWithRelationships(array $categoryCodes, string $language): Collection
    {
        return Category::with([
            'parents',
            'translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }
        ])->whereIn('category_code', $categoryCodes)->get();
    }

    /**
     * Get all categories with relationships
     */
    public function getAllWithRelationships(string $language): Collection
    {
        return Category::with([
            'parents',
            'children',
            'translations' => function ($query) use ($language) {
                $query->where('language', $language);
            }
        ])->get();
    }

    /**
     * Get sub-categories for a category
     */
    public function getSubCategories(string $categoryCode, string $language): array
    {
        return DB::table('category_hierarchy')
            ->where('parent_code', $categoryCode)
            ->join('lkp_category', 'category_hierarchy.category_code', '=', 'lkp_category.category_code')
            ->leftJoin('lkp_category_translation', function ($join) use ($language) {
                $join->on('lkp_category.category_code', '=', 'lkp_category_translation.category_code')
                     ->where('lkp_category_translation.language', '=', $language);
            })
            ->select(
                'lkp_category.category_code',
                'lkp_category_translation.name'
            )
            ->get()
            ->toArray();
    }

    /**
     * Check if category has sub-categories
     */
    public function hasSubCategories(string $categoryCode): bool
    {
        return DB::table('category_hierarchy')
            ->where('parent_code', $categoryCode)
            ->exists();
    }

    /**
     * Get category details with translations
     */
    public function getDetailsWithTranslations(array $categoryCodes, string $language): array
    {
        return DB::table('lkp_category')
            ->leftJoin('lkp_category_translation', function ($join) use ($language) {
                $join->on('lkp_category.category_code', '=', 'lkp_category_translation.category_code')
                     ->where('lkp_category_translation.language', '=', $language);
            })
            ->whereIn('lkp_category.category_code', $categoryCodes)
            ->select(
                'lkp_category.category_code',
                'lkp_category_translation.name as title'
            )
            ->get()
            ->toArray();
    }

    /**
     * Get hierarchy relationships
     */
    public function getHierarchy(): array
    {
        return DB::table('category_hierarchy')
            ->select('category_code', 'parent_code')
            ->get()
            ->groupBy('category_code')
            ->toArray();
    }

    /**
     * Get all categories with hierarchy data in a single optimized query
     * This method eliminates the N+1 query problem by fetching all data at once
     */
    public function getCategoryTreeData(string $language): array
    {
        return DB::table('lkp_category as c')
            ->leftJoin('category_hierarchy as ch', 'c.category_code', '=', 'ch.category_code')
            ->leftJoin('lkp_category_translation as ct', function($join) use ($language) {
                $join->on('c.category_code', '=', 'ct.category_code')
                     ->where('ct.language', '=', $language);
            })
            ->select(
                'c.category_code',
                'ct.name',
                DB::raw('GROUP_CONCAT(ch.parent_code) as parent_codes')
            )
            ->groupBy('c.category_code', 'ct.name')
            ->get()
            ->toArray();
    }

    /**
     * Get all categories with translations in a single optimized query
     * This replaces the getAllWithRelationships method for better performance
     */
    public function getAllCategoriesWithTranslations(string $language): array
    {
        return DB::table('lkp_category as c')
            ->leftJoin('lkp_category_translation as ct', function($join) use ($language) {
                $join->on('c.category_code', '=', 'ct.category_code')
                     ->where('ct.language', '=', $language);
            })
            ->select('c.category_code', 'ct.name')
            ->get()
            ->toArray();
    }
}
