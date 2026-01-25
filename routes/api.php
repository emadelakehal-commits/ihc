<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExcelImportController;
use App\Http\Controllers\Api\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['api.response', 'api.logging', 'api.rate.limit'])->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::get('products/{productCode}/details', [ProductController::class, 'showProduct']);
    Route::put('products/{productCode}/update', [ProductController::class, 'updateProduct']);
    Route::post('main-products/{mainProductCode}/products', [ProductController::class, 'storeVariants']);
    Route::get('main-products/{mainProductCode}', [ProductController::class, 'showMainProduct']);
    Route::put('main-products/{mainProductCode}', [ProductController::class, 'updateMainProduct']);
    Route::post('main-products/{mainProductCode}/images', [ProductController::class, 'uploadMainProductImages']);
    Route::post('products/{productCode}/images', [ImageController::class, 'uploadProductImages']);
    Route::post('products/{productItemCode}/item-images', [ImageController::class, 'uploadProductItemImages']);
    Route::post('categories/details', [CategoryController::class, 'getCategories']);
    Route::get('categories/tree', [CategoryController::class, 'getCategoryTree']);
    Route::get('categories/sub-categories', [CategoryController::class, 'getSubCategories']);
    Route::post('categories/get-details', [CategoryController::class, 'getCategoryDetails']);
    Route::get('process-excel', [ExcelImportController::class, 'processExcel']);
    Route::get('related-products', [ProductController::class, 'getRelatedProducts']);
    Route::post('products/by-categories', [ProductController::class, 'getProductsByCategories']);
    Route::post('products/get-product-code', [ProductController::class, 'getProductCodeByIsku']);
    Route::post('products/get-by-codes', [ProductController::class, 'getProductsByCodes']);
    Route::get('products/{productCode}/items', [ProductController::class, 'getProductItems']);
    Route::get('entities/by-tag/{tagCode}/{lang}', [ProductController::class, 'getEntitiesByTag']);
    Route::post('images/download-from-drive', [ImageController::class, 'downloadFromDrive']);
});
