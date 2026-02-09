<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductUtilityService
{
    /**
     * Convert meters to millimeters.
     */
    public function convertToMm(?string $value): ?string
    {
        try {
            if (empty($value)) return null;

            // If value is already in mm (contains 'mm'), return as is
            if (strpos(strtolower($value), 'mm') !== false) {
                return str_replace(['mm', ' '], '', $value);
            }

            // If value is in meters (contains 'm'), convert to mm
            if (strpos(strtolower($value), 'm') !== false) {
                $numericValue = floatval(str_replace(['m', ' '], '', $value));
                return (string)($numericValue * 1000); // Convert m to mm
            }

            // If no unit specified, assume it's in meters and convert to mm
            $numericValue = floatval($value);
            return (string)($numericValue * 1000);
        } catch (\Exception $e) {
            Log::error('Error in convertToMm', [
                'value' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Parse categories from comma-separated string.
     * Handles values that may contain commas within quotes or escaped.
     */
    public function parseCategories(?string $categoriesString): array
    {
        try {
            if (empty($categoriesString)) return [];

            // If the string contains quotes, handle quoted values
            if (strpos($categoriesString, '"') !== false) {
                $categories = [];
                $inQuotes = false;
                $current = '';

                for ($i = 0; $i < strlen($categoriesString); $i++) {
                    $char = $categoriesString[$i];

                    if ($char === '"') {
                        $inQuotes = !$inQuotes;
                    } elseif ($char === ',' && !$inQuotes) {
                        $categories[] = trim($current);
                        $current = '';
                    } else {
                        $current .= $char;
                    }
                }

                if (!empty($current)) {
                    $categories[] = trim($current);
                }

                return array_filter($categories); // Remove empty values
            }

            // Simple comma splitting for unquoted values
            return array_filter(array_map('trim', explode(',', $categoriesString)));
        } catch (\Exception $e) {
            Log::error('Error in parseCategories', [
                'categoriesString' => $categoriesString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return []; // Return empty array on error
        }
    }

    /**
     * Parse tags from comma-separated string.
     * Handles values that may contain commas within quotes or escaped.
     */
    public function parseTags(?string $tagsString): array
    {
        try {
            if (empty($tagsString)) return [];

            // If the string contains quotes, handle quoted values
            if (strpos($tagsString, '"') !== false) {
                $tags = [];
                $inQuotes = false;
                $current = '';

                for ($i = 0; $i < strlen($tagsString); $i++) {
                    $char = $tagsString[$i];

                    if ($char === '"') {
                        $inQuotes = !$inQuotes;
                    } elseif ($char === ',' && !$inQuotes) {
                        $tags[] = trim($current);
                        $current = '';
                    } else {
                        $current .= $char;
                    }
                }

                if (!empty($current)) {
                    $tags[] = trim($current);
                }

                return array_filter($tags); // Remove empty values
            }

            // Simple comma splitting for unquoted values
            return array_filter(array_map('trim', explode(',', $tagsString)));
        } catch (\Exception $e) {
            Log::error('Error in parseTags', [
                'tagsString' => $tagsString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return []; // Return empty array on error
        }
    }

    /**
     * Get or create category (case insensitive).
     */
    public function getOrCreateCategory(?string $categoryName, string $language): ?string
    {
        try {
            if (empty($categoryName)) return null;

            // Check if category exists (case insensitive)
            $existingCategory = DB::table('lkp_category')
                ->whereRaw('LOWER(category_code) = ?', [strtolower($categoryName)])
                ->first();

            if ($existingCategory) {
                return $existingCategory->category_code;
            }

            // Check if category exists by translation (case insensitive)
            $existingTranslation = DB::table('lkp_category_translation')
                ->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
                ->where('language', $language)
                ->first();

            if ($existingTranslation) {
                return $existingTranslation->category_code;
            }

            // Create new category
            $categoryCode = strtoupper(str_replace(' ', '_', $categoryName));

            // Ensure unique category code
            $counter = 1;
            $originalCode = $categoryCode;
            while (DB::table('lkp_category')->where('category_code', $categoryCode)->exists()) {
                $categoryCode = $originalCode . '_' . $counter;
                $counter++;
            }

            // Insert category
            DB::table('lkp_category')->insert([
                'category_code' => $categoryCode,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert category translation
            DB::table('lkp_category_translation')->insert([
                'category_code' => $categoryCode,
                'language' => $language,
                'name' => $categoryName,
            ]);

            return $categoryCode;
        } catch (\Exception $e) {
            Log::error('Error in getOrCreateCategory', [
                'categoryName' => $categoryName,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Get or create tag (case insensitive).
     */
    public function getOrCreateTag(?string $tagName, string $language): ?string
    {
        try {
            if (empty($tagName)) return null;

            // Check if tag exists (case insensitive)
            $existingTag = DB::table('lkp_tag')
                ->whereRaw('LOWER(tag_code) = ?', [strtolower($tagName)])
                ->first();

            if ($existingTag) {
                return $existingTag->tag_code;
            }

            // Check if tag exists by translation (case insensitive)
            $existingTranslation = DB::table('lkp_tag_translation')
                ->whereRaw('LOWER(name) = ?', [strtolower($tagName)])
                ->where('language', $language)
                ->first();

            if ($existingTranslation) {
                return $existingTranslation->tag_code;
            }

            // Create new tag
            $tagCode = strtoupper(str_replace(' ', '_', $tagName));

            // Ensure unique tag code
            $counter = 1;
            $originalCode = $tagCode;
            while (DB::table('lkp_tag')->where('tag_code', $tagCode)->exists()) {
                $tagCode = $originalCode . '_' . $counter;
                $counter++;
            }

            // Insert tag
            DB::table('lkp_tag')->insert([
                'tag_code' => $tagCode,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert tag translation
            DB::table('lkp_tag_translation')->insert([
                'tag_code' => $tagCode,
                'language' => $language,
                'name' => $tagName,
            ]);

            return $tagCode;
        } catch (\Exception $e) {
            Log::error('Error in getOrCreateTag', [
                'tagName' => $tagName,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Get or create item tag (case insensitive).
     */
    public function getOrCreateItemTag(?string $itemTagName, string $language): ?string
    {
        try {
            if (empty($itemTagName)) return null;

            // Check if item tag exists (case insensitive)
            $existingItemTag = DB::table('lkp_item_tag')
                ->whereRaw('LOWER(item_tag_code) = ?', [strtolower($itemTagName)])
                ->first();

            if ($existingItemTag) {
                return $existingItemTag->item_tag_code;
            }

            // Check if item tag exists by translation (case insensitive)
            $existingTranslation = DB::table('lkp_item_tag_translation')
                ->whereRaw('LOWER(name) = ?', [strtolower($itemTagName)])
                ->where('language', $language)
                ->first();

            if ($existingTranslation) {
                return $existingTranslation->item_tag_code;
            }

            // Create new item tag
            $itemTagCode = strtoupper(str_replace(' ', '_', $itemTagName));

            // Ensure unique item tag code
            $counter = 1;
            $originalCode = $itemTagCode;
            while (DB::table('lkp_item_tag')->where('item_tag_code', $itemTagCode)->exists()) {
                $itemTagCode = $originalCode . '_' . $counter;
                $counter++;
            }

            // Insert item tag
            DB::table('lkp_item_tag')->insert([
                'item_tag_code' => $itemTagCode,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert item tag translation
            DB::table('lkp_item_tag_translation')->insert([
                'item_tag_code' => $itemTagCode,
                'language' => $language,
                'name' => $itemTagName,
            ]);

            return $itemTagCode;
        } catch (\Exception $e) {
            Log::error('Error in getOrCreateItemTag', [
                'itemTagName' => $itemTagName,
                'language' => $language,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Ensure attribute exists in lkp_attribute table (outside transaction).
     */
    public function ensureAttributeExists(?string $attributeName): void
    {
        try {
            if (empty($attributeName)) return;

            // Check if attribute exists (case insensitive)
            $existingAttribute = DB::table('lkp_attribute')
                ->whereRaw('LOWER(name) = ?', [strtolower($attributeName)])
                ->first();

            if (!$existingAttribute) {
                // Create new attribute outside current transaction
                DB::transaction(function () use ($attributeName) {
                    DB::table('lkp_attribute')->insert([
                        'name' => $attributeName,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
            }
        } catch (\Exception $e) {
            Log::error('Error in ensureAttributeExists', [
                'attributeName' => $attributeName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - just log the error
        }
    }

    /**
     * Get or create attribute (case insensitive).
     */
    public function getOrCreateAttribute(?string $attributeName): ?string
    {
        try {
            if (empty($attributeName)) return null;

            // Check if attribute exists (case insensitive)
            $existingAttribute = DB::table('lkp_attribute')
                ->whereRaw('LOWER(name) = ?', [strtolower($attributeName)])
                ->first();

            if ($existingAttribute) {
                return $existingAttribute->name;
            }

            // Create new attribute
            DB::table('lkp_attribute')->insert([
                'name' => $attributeName,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $attributeName;
        } catch (\Exception $e) {
            Log::error('Error in getOrCreateAttribute', [
                'attributeName' => $attributeName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null; // Return null on error
        }
    }

    /**
     * Get column value by trying multiple possible column names.
     */
    public function getColumnValue(array $columnMapping, array $row, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            if (isset($columnMapping[$name])) {
                return isset($row[$columnMapping[$name]]) ? $row[$columnMapping[$name]] : null;
            }
        }
        return null; // Return null if none of the column names are found
    }
}