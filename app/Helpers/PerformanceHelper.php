<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PerformanceHelper
{
    /**
     * Monitor and log performance metrics for category operations
     */
    public static function monitorCategoryTreePerformance(string $language, callable $operation)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $startQueryCount = count(DB::getQueryLog());

        try {
            $result = $operation();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            $endQueryCount = count(DB::getQueryLog());
            
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsage = ($endMemory - $startMemory) / 1024; // Convert to KB
            $queryCount = $endQueryCount - $startQueryCount;
            
            Log::info('Category Tree Performance', [
                'language' => $language,
                'execution_time_ms' => round($executionTime, 2),
                'memory_usage_kb' => round($memoryUsage, 2),
                'query_count' => $queryCount,
                'result_count' => is_array($result) ? count($result) : 0,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Category Tree Performance Error', [
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Clear category tree cache for a specific language
     */
    public static function clearCategoryTreeCache(string $language): bool
    {
        $cacheKey = "category_tree_{$language}";
        return Cache::forget($cacheKey);
    }

    /**
     * Clear all category-related caches
     */
    public static function clearAllCategoryCaches(): void
    {
        // Clear category tree caches
        $languages = ['en', 'ar']; // Add all supported languages
        foreach ($languages as $language) {
            self::clearCategoryTreeCache($language);
        }
        
        // Clear image caches (if using a pattern)
        Cache::flush(); // Be careful with this in production
    }

    /**
     * Get cache statistics for category operations
     */
    public static function getCategoryCacheStats(): array
    {
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'category_tree_keys' => self::getCategoryTreeCacheKeys(),
            'cache_size_estimate' => self::estimateCacheSize()
        ];
    }

    /**
     * Get list of category tree cache keys
     */
    private static function getCategoryTreeCacheKeys(): array
    {
        $languages = ['en', 'ar']; // Add all supported languages
        $keys = [];
        
        foreach ($languages as $language) {
            $cacheKey = "category_tree_{$language}";
            if (Cache::has($cacheKey)) {
                $keys[] = $cacheKey;
            }
        }
        
        return $keys;
    }

    /**
     * Estimate cache size (approximate)
     */
    private static function estimateCacheSize(): string
    {
        // This is a rough estimate and may vary based on cache driver
        $keys = self::getCategoryTreeCacheKeys();
        $estimatedSize = count($keys) * 50; // Rough estimate of 50KB per tree
        
        return $estimatedSize . ' KB';
    }
}