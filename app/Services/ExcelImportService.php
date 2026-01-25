<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExcelImportService
{
    /**
     * Process Excel file and create/update products and product items
     */
    public function processExcelFile(string $filePath, string $language): array
    {
        // Load Excel file
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Failed to load Excel file: ' . $e->getMessage());
        }

        // Check if we have enough rows
        if (count($rows) < 2) {
            throw new \Exception('Excel file must contain at least a header row and one data row');
        }

        // Get header row and create column mapping
        $headerRow = array_shift($rows);
        $columnMapping = [];
        foreach ($headerRow as $index => $header) {
            if (!empty(trim($header))) {
                $columnMapping[trim(strtolower($header))] = $index;
            }
        }

        $processedProducts = [];
        $productNameToCodeMap = [];
        $lastProductCode = null;
        $lastProductName = null;
        $iskuDuplicates = [];
        $seenIskus = [];
        $totalSkipped = 0;
        $totalProductItemsInFile = 0;
        $totalNewProductsInserted = 0;
        $totalNewProductItemsInserted = 0;
        $totalProductsUpdated = 0;
        $totalProductItemsUpdated = 0;
        $updatedProductCodes = [];
        $updatedIskus = [];

        DB::beginTransaction();

        try {
            // Process each data row
            foreach ($rows as $rowIndex => $row) {
                try {
                    // Map columns using header names
                    $productData = [
                        'product_code' => isset($columnMapping['product code']) ? $row[$columnMapping['product code']] : null,
                        'product_name' => isset($columnMapping['product name']) ? $row[$columnMapping['product name']] : null,
                        'product_summary' => isset($columnMapping['product description']) ? $row[$columnMapping['product description']] : null,
                        'supplier_code' => isset($columnMapping['supplier product item code']) ? $row[$columnMapping['supplier product item code']] : null,
                        'isku' => isset($columnMapping['isku']) ? $row[$columnMapping['isku']] : null,
                        'cost' => isset($columnMapping['cost (net price eur)']) ? $row[$columnMapping['cost (net price eur)']] : null,
                        'rrp' => isset($columnMapping['rrp (eur)']) ? $row[$columnMapping['rrp (eur)']] : null,
                        'diameter' => $this->convertToMm(isset($columnMapping['diameter (m)']) ? $row[$columnMapping['diameter (m)']] : null),
                        'length' => $this->convertToMm(isset($columnMapping['length (m)']) ? $row[$columnMapping['length (m)']] : null),
                        'width' => $this->convertToMm(isset($columnMapping['width (m)']) ? $row[$columnMapping['width (m)']] : null),
                        'covered_area' => isset($columnMapping['covered area (m2)']) ? $row[$columnMapping['covered area (m2)']] : null,
                        'thickness' => $this->convertToMm(isset($columnMapping['thickness(m)']) ? $row[$columnMapping['thickness(m)']] : null),
                        'watt_m2' => isset($columnMapping['watt/m2']) ? $row[$columnMapping['watt/m2']] : null,
                        'ip_class' => isset($columnMapping['ip class']) ? $row[$columnMapping['ip class']] : null,
                        'cold_lead' => isset($columnMapping['cold lead']) ? $row[$columnMapping['cold lead']] : null,
                        'cold_lead_length' => isset($columnMapping['cold lead length']) ? $row[$columnMapping['cold lead length']] : null,
                        'outside_jacket_material' => isset($columnMapping['outside jacket martial']) ? $row[$columnMapping['outside jacket martial']] : null,
                        'inside_jacket_material' => isset($columnMapping['inside jacket martial']) ? $row[$columnMapping['inside jacket martial']] : null,
                        'certificates' => isset($columnMapping['certificates']) ? $row[$columnMapping['certificates']] : null,
                        'voltage' => isset($columnMapping['voltage (v)']) ? $row[$columnMapping['voltage (v)']] : null,
                        'total_wattage' => isset($columnMapping['total wattage (w)']) ? $row[$columnMapping['total wattage (w)']] : null,
                        'amp' => isset($columnMapping['amp (a)']) ? $row[$columnMapping['amp (a)']] : null,
                        'ohm' => isset($columnMapping['ohm']) ? $row[$columnMapping['ohm']] : null,
                        'categories' => $this->parseCategories(isset($columnMapping['product cats']) ? $row[$columnMapping['product cats']] : null),
                        'sub_categories' => $this->parseCategories(isset($columnMapping['product sub cat1']) ? $row[$columnMapping['product sub cat1']] : null),
                        'product_line' => isset($columnMapping['product line']) ? $row[$columnMapping['product line']] : null,
                        'tags' => $this->parseTags(isset($columnMapping['product tags']) ? $row[$columnMapping['product tags']] : null),
                        'item_tags' => $this->parseTags(isset($columnMapping['product item tags']) ? $row[$columnMapping['product item tags']] : null),
                        'fire_retardent' => isset($columnMapping['fire-retardent']) ? $row[$columnMapping['fire-retardent']] : null,
                        'product_warranty' => isset($columnMapping['product warranty']) ? $row[$columnMapping['product warranty']] : null,
                        'self_adhesive' => isset($columnMapping['self adhesive']) ? $row[$columnMapping['self adhesive']] : null,
                        'includes' => isset($columnMapping['includes']) ? $row[$columnMapping['includes']] : null,
                        'related_products' => $this->parseRelatedProducts(isset($columnMapping['related products']) ? $row[$columnMapping['related products']] : null),
                        'product_item_code' => isset($columnMapping['supplier product item code']) ? $row[$columnMapping['supplier product item code']] : null,
                        'title' => isset($columnMapping['product item name']) ? $row[$columnMapping['product item name']] : null,
                        'short_desc' => isset($columnMapping['product item short descripton']) ? $row[$columnMapping['product item short descripton']] : null,
                        'variation_text' => isset($columnMapping['variation text']) ? $row[$columnMapping['variation text']] : null,
                        'availability' => $this->parseAvailability($this->getColumnValue($columnMapping, $row, ['stock/on demand', 'stock/ on demand', 'stock/on demand', 'stock / on demand'])),
                    ];

                    // Handle case where product_code is empty - use last processed product
                    if (empty($productData['product_code']) && !empty($lastProductCode)) {
                        $productData['product_code'] = $lastProductCode;
                        if (empty($productData['product_name']) && !empty($lastProductName)) {
                            $productData['product_name'] = $lastProductName;
                        }
                    }

                    // Skip rows that don't have product_item_code (empty rows)
                    if (empty($productData['product_item_code'])) {
                        Log::info('processExcel: Silently skipping row ' . ($rowIndex + 2) . ' - missing product_item_code');
                        $totalSkipped++;
                        continue;
                    }

                    $totalProductItemsInFile++;

                    // Check for duplicate ISKU values
                    if (!empty($productData['isku'])) {
                        if (isset($seenIskus[$productData['isku']])) {
                            $iskuDuplicates[] = [
                                'isku' => $productData['isku'],
                                'first_occurrence_row' => $seenIskus[$productData['isku']],
                                'duplicate_row' => $rowIndex + 2
                            ];
                            continue;
                        } else {
                            $seenIskus[$productData['isku']] = $rowIndex + 2;
                        }
                    }

                    // Update last processed product info
                    if (!empty($productData['product_code']) && !empty($productData['product_name'])) {
                        $lastProductCode = $productData['product_code'];
                        $lastProductName = $productData['product_name'];
                    }

                    // Check if product already exists
                    $productExists = Product::where('product_code', $productData['product_code'])->exists();

                    if (!$productExists) {
                        Product::create(['product_code' => $productData['product_code']]);
                        \App\Models\ProductTranslation::create([
                            'product_code' => $productData['product_code'],
                            'language' => $language,
                            'title' => $productData['product_name'],
                            'summary' => $productData['product_summary'] ?? $productData['product_name'],
                            'description' => $productData['product_name']
                        ]);
                        $totalNewProductsInserted++;
                    } else {
                        if (!isset($updatedProductCodes[$productData['product_code']])) {
                            $updatedProductCodes[$productData['product_code']] = true;
                            $totalProductsUpdated++;
                        }
                    }

                    // Check if product item already exists
                    $existingProductItem = ProductItem::where('isku', $productData['isku'])->first();

                    if (!$existingProductItem) {
                        $productItem = ProductItem::create([
                            'product_item_code' => $productData['product_item_code'],
                            'isku' => $productData['isku'],
                            'product_code' => $productData['product_code'],
                            'is_active' => true,
                            'cost' => $productData['cost'],
                            'cost_currency' => 'EUR',
                            'rrp' => $productData['rrp'],
                            'rrp_currency' => 'EUR',
                            'availability' => $productData['availability'],
                        ]);
                        $totalNewProductItemsInserted++;
                    } else {
                        $existingProductItem->update([
                            'product_item_code' => $productData['product_item_code'],
                            'product_code' => $productData['product_code'],
                            'is_active' => true,
                            'cost' => $productData['cost'],
                            'cost_currency' => 'EUR',
                            'rrp' => $productData['rrp'],
                            'rrp_currency' => 'EUR',
                            'availability' => $productData['availability'],
                        ]);
                        $productItem = $existingProductItem;
                        $totalProductItemsUpdated++;
                    }

                    // Create product item translation
                    DB::table('product_item_translation')->insertOrIgnore([
                        'isku' => $productData['isku'],
                        'language' => $language,
                        'title' => $productData['title'],
                        'short_desc' => $productData['short_desc'],
                        'variation_text' => $productData['variation_text'],
                    ]);

                    // Create attributes
                    $attributes = [
                        'diameter' => $productData['diameter'],
                        'length' => $productData['length'],
                        'width' => $productData['width'],
                        'covered_area' => $productData['covered_area'],
                        'thickness' => $productData['thickness'],
                        'watt_m2' => $productData['watt_m2'],
                        'ip_class' => $productData['ip_class'],
                        'cold_lead' => $productData['cold_lead'],
                        'cold_lead_length' => $productData['cold_lead_length'],
                        'outside_jacket_material' => $productData['outside_jacket_material'],
                        'inside_jacket_material' => $productData['inside_jacket_material'],
                        'certificates' => $productData['certificates'],
                        'voltage' => $productData['voltage'],
                        'total_wattage' => $productData['total_wattage'],
                        'amp' => $productData['amp'],
                        'ohm' => $productData['ohm'],
                        'fire_retardent' => $productData['fire_retardent'],
                        'product_warranty' => $productData['product_warranty'],
                        'self_adhesive' => $productData['self_adhesive'],
                        'includes' => $productData['includes'],
                    ];

                    foreach ($attributes as $attrName => $value) {
                        if (!empty($value)) {
                            $this->ensureAttributeExists($attrName);
                            DB::table('product_attribute_value')->upsert([
                                'isku' => $productData['isku'],
                                'attribute_name' => $attrName,
                                'language' => $language,
                                'value' => $value,
                            ], ['isku', 'attribute_name', 'language'], ['value']);
                        }
                    }

                    // Create delivery times
                    $deliveryMin = $productData['availability'] === 's' ? 1 : 10;
                    $deliveryMax = $productData['availability'] === 's' ? 3 : 15;

                    DB::table('product_delivery')->insertOrIgnore([
                        'isku' => $productData['isku'],
                        'domain_id' => 'LT',
                        'delivery_min' => $deliveryMin,
                        'delivery_max' => $deliveryMax,
                    ]);

                    // Create categories (skip invalid ones instead of throwing exception)
                    $allCategories = array_merge($productData['categories'], $productData['sub_categories']);

                    foreach ($allCategories as $categoryName) {
                        if (!empty($categoryName)) {
                            $normalizedInput = preg_replace('/[\s\-_\.\W]/', '', strtolower($categoryName));
                            $existingCategory = DB::table('lkp_category')
                                ->join('lkp_category_translation', 'lkp_category.category_code', '=', 'lkp_category_translation.category_code')
                                ->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(REPLACE(lkp_category_translation.name, \' \', \'\'), \'-\', \'\'), \'_\', \'\'), \'.\', \'\')) = ?', [$normalizedInput])
                                ->where('lkp_category_translation.language', $language)
                                ->select('lkp_category.*')
                                ->first();

                            if ($existingCategory) {
                                DB::table('product_category')->insertOrIgnore([
                                    'product_code' => $productData['product_code'],
                                    'category_code' => $existingCategory->category_code,
                                ]);
                            } else {
                                // Log invalid category but continue processing
                                Log::warning('processExcel: Skipping invalid category: ' . $categoryName . ' for product ' . $productData['product_code']);
                            }
                        }
                    }

                    // Create tags and item tags
                    foreach ($productData['tags'] as $tagName) {
                        if (!empty($tagName)) {
                            $tagCode = $this->getOrCreateTag($tagName, $language);
                            if ($tagCode) {
                                DB::table('product_tag')->insertOrIgnore([
                                    'product_code' => $productData['product_code'],
                                    'tag_code' => $tagCode,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }

                    foreach ($productData['item_tags'] as $itemTagName) {
                        if (!empty($itemTagName)) {
                            $itemTagCode = $this->getOrCreateItemTag($itemTagName, $language);
                            if ($itemTagCode) {
                                DB::table('product_item_tag')->insert([
                                    'isku' => $productData['isku'],
                                    'item_tag_code' => $itemTagCode,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }

                    $processedProducts[] = $productData;

                } catch (\Exception $e) {
                    throw $e; // Re-throw to be handled by caller
                }
            }

            // Process related products
            $relatedProductsCount = 0;
            foreach ($rows as $rowIndex => $row) {
                $productCode = isset($columnMapping['product code']) ? $row[$columnMapping['product code']] : null;
                $isku = isset($columnMapping['isku']) ? $row[$columnMapping['isku']] : null;
                $relatedProductsString = isset($columnMapping['related products']) ? $row[$columnMapping['related products']] : null;

                if (empty($relatedProductsString)) continue;

                $fromEntityType = !empty($isku) ? 'product_item' : 'product';
                $fromEntityCode = !empty($isku) ? $isku : $productCode;

                if (!$fromEntityCode) continue;

                $relatedProducts = $this->parseRelatedProducts($relatedProductsString);

                foreach ($relatedProducts as $relatedEntityCode) {
                    $relatedEntityCode = trim($relatedEntityCode);
                    if (empty($relatedEntityCode)) continue;

                    $toEntityType = null;
                    $toEntityCode = null;

                    if (Product::where('product_code', $relatedEntityCode)->exists()) {
                        $toEntityType = 'product';
                        $toEntityCode = $relatedEntityCode;
                    } elseif (ProductItem::where('isku', $relatedEntityCode)->exists()) {
                        $toEntityType = 'product_item';
                        $toEntityCode = $relatedEntityCode;
                    }

                    if ($toEntityType && $toEntityCode) {
                        DB::table('product_related')->insertOrIgnore([
                            'from_entity_type' => $fromEntityType,
                            'from_entity_code' => $fromEntityCode,
                            'to_entity_type' => $toEntityType,
                            'to_entity_code' => $toEntityCode,
                            'relation_type' => 'related',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('product_related')->insertOrIgnore([
                            'from_entity_type' => $toEntityType,
                            'from_entity_code' => $toEntityCode,
                            'to_entity_type' => $fromEntityType,
                            'to_entity_code' => $fromEntityCode,
                            'relation_type' => 'related',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $relatedProductsCount++;
                    }
                }
            }

            DB::commit();

            return [
                'total_product_items_in_file' => $totalProductItemsInFile,
                'total_new_product_items_inserted' => $totalNewProductItemsInserted,
                'total_new_products_inserted' => $totalNewProductsInserted,
                'total_failed' => 0, // Would be populated with actual failed rows
                'total_skipped' => $totalSkipped,
                'total_products_updated' => $totalProductsUpdated,
                'total_product_items_updated' => $totalProductItemsUpdated,
                'total_duplicates' => count($iskuDuplicates),
                'duplicate_iskus' => $iskuDuplicates,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Helper methods (same as in original controller)
    private function convertToMm($value)
    {
        if (empty($value)) return null;
        if (strpos(strtolower($value), 'mm') !== false) {
            return str_replace(['mm', ' '], '', $value);
        }
        if (strpos(strtolower($value), 'm') !== false) {
            $numericValue = floatval(str_replace(['m', ' '], '', $value));
            return $numericValue * 1000;
        }
        $numericValue = floatval($value);
        return $numericValue * 1000;
    }

    private function parseCategories($categoriesString)
    {
        if (empty($categoriesString)) return [];
        return array_filter(array_map('trim', explode(',', $categoriesString)));
    }

    private function parseTags($tagsString)
    {
        if (empty($tagsString)) return [];
        return array_filter(array_map('trim', explode(',', $tagsString)));
    }

    private function parseRelatedProducts($relatedProductsString)
    {
        if (empty($relatedProductsString)) return [];
        return array_filter(array_map('trim', explode(',', $relatedProductsString)));
    }

    private function parseAvailability($availabilityString)
    {
        if (empty($availabilityString)) return 'o';
        $normalized = strtolower(trim($availabilityString));
        if ($normalized === 's') return 's';
        if ($normalized === 'o') return 'o';
        if (strpos($normalized, 'stock') !== false) return 's';
        if (strpos($normalized, 'on demand') !== false || strpos($normalized, 'on-demand') !== false) return 'o';
        return 'o';
    }

    private function getColumnValue($columnMapping, $row, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            if (isset($columnMapping[$name])) {
                return isset($row[$columnMapping[$name]]) ? $row[$columnMapping[$name]] : null;
            }
        }
        return null;
    }

    private function ensureAttributeExists($attributeName)
    {
        if (empty($attributeName)) return;
        $existingAttribute = DB::table('lkp_attribute')
            ->whereRaw('LOWER(name) = ?', [strtolower($attributeName)])
            ->first();
        if (!$existingAttribute) {
            DB::transaction(function () use ($attributeName) {
                DB::table('lkp_attribute')->insert([
                    'name' => $attributeName,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }
    }

    private function getOrCreateTag($tagName, $language)
    {
        $existingTag = DB::table('lkp_tag')
            ->whereRaw('LOWER(tag_code) = ?', [strtolower($tagName)])
            ->first();
        if ($existingTag) return $existingTag->tag_code;

        $existingTranslation = DB::table('lkp_tag_translation')
            ->whereRaw('LOWER(name) = ?', [strtolower($tagName)])
            ->where('language', $language)
            ->first();
        if ($existingTranslation) return $existingTranslation->tag_code;

        $tagCode = strtoupper(str_replace(' ', '_', $tagName));
        $counter = 1;
        $originalCode = $tagCode;
        while (DB::table('lkp_tag')->where('tag_code', $tagCode)->exists()) {
            $tagCode = $originalCode . '_' . $counter;
            $counter++;
        }

        DB::table('lkp_tag')->insert([
            'tag_code' => $tagCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lkp_tag_translation')->insert([
            'tag_code' => $tagCode,
            'language' => $language,
            'name' => $tagName,
        ]);

        return $tagCode;
    }

    private function getOrCreateItemTag($itemTagName, $language)
    {
        $existingItemTag = DB::table('lkp_item_tag')
            ->whereRaw('LOWER(item_tag_code) = ?', [strtolower($itemTagName)])
            ->first();
        if ($existingItemTag) return $existingItemTag->item_tag_code;

        $existingTranslation = DB::table('lkp_item_tag_translation')
            ->whereRaw('LOWER(name) = ?', [strtolower($itemTagName)])
            ->where('language', $language)
            ->first();
        if ($existingTranslation) return $existingTranslation->item_tag_code;

        $itemTagCode = strtoupper(str_replace(' ', '_', $itemTagName));
        $counter = 1;
        $originalCode = $itemTagCode;
        while (DB::table('lkp_item_tag')->where('item_tag_code', $itemTagCode)->exists()) {
            $itemTagCode = $originalCode . '_' . $counter;
            $counter++;
        }

        DB::table('lkp_item_tag')->insert([
            'item_tag_code' => $itemTagCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lkp_item_tag_translation')->insert([
            'item_tag_code' => $itemTagCode,
            'language' => $language,
            'name' => $itemTagName,
        ]);

        return $itemTagCode;
    }
}
