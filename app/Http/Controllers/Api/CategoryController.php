<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCategoriesRequest;
use App\Http\Requests\GetCategoryTreeRequest;
use App\Http\Requests\GetSubCategoriesRequest;
use App\Http\Requests\GetCategoryDetailsRequest;
use App\Services\CategoryService;
use App\Helpers\PerformanceHelper;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * Get category details for an array of category codes.
     */
    public function getCategories(GetCategoriesRequest $request): JsonResponse
    {
        try {
            $data = $this->categoryService->getCategories(
                $request->input('categories'),
                $request->input('lang')
            );

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the complete category tree with all categories and their hierarchy.
     */
    public function getCategoryTree(GetCategoryTreeRequest $request): JsonResponse
    {
        try {
            $language = $request->input('lang');
            
            // Monitor performance for the category tree operation
            $data = PerformanceHelper::monitorCategoryTreePerformance($language, function() use ($language) {
                return $this->categoryService->getCategoryTree($language);
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting category tree',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sub-categories for a given category code
     */
    public function getSubCategories(GetSubCategoriesRequest $request): JsonResponse
    {
        try {
            $data = $this->categoryService->getSubCategories(
                $request->input('category_code'),
                $request->input('lang')
            );

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting sub-categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category details for a list of category codes
     */
    public function getCategoryDetails(GetCategoryDetailsRequest $request): JsonResponse
    {
        try {
            $data = $this->categoryService->getCategoryDetails(
                $request->input('category_codes'),
                $request->input('lang')
            );

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting category details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
