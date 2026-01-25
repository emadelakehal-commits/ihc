 <?php

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Check if file exists
$fileName = 'products.xlsx'; // Change this to your actual file name
$filePath = storage_path('app/public/excel/' . $fileName);

if (!file_exists($filePath)) {
    $altFilePath = storage_path('app/public/' . $fileName);
    if (file_exists($altFilePath)) {
        $filePath = $altFilePath;
    } else {
        die("Excel file not found in either location:\n- storage/app/public/excel/{$fileName}\n- storage/app/public/{$fileName}\n");
    }
}

echo "Reading Excel file: {$filePath}\n\n";

try {
    // Load Excel file
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    echo "=== EXCEL FILE ANALYSIS ===\n\n";

    // Show total rows
    echo "Total rows in Excel file: " . count($rows) . "\n\n";

    // Show first row (header)
    echo "=== FIRST ROW (HEADER) ===\n";
    if (isset($rows[0])) {
        foreach ($rows[0] as $index => $value) {
            $columnLetter = chr(65 + $index); // Convert index to column letter (A, B, C, etc.)
            echo "Column " . ($index + 1) . " ({$columnLetter}): " . (empty($value) ? '[EMPTY]' : "\"{$value}\"") . "\n";
        }
    }
    echo "\n";

    // Show second row (first data row)
    echo "=== SECOND ROW (FIRST DATA ROW) ===\n";
    if (isset($rows[1])) {
        foreach ($rows[1] as $index => $value) {
            $columnLetter = chr(65 + $index);
            echo "Column " . ($index + 1) . " ({$columnLetter}): " . (empty($value) ? '[EMPTY]' : "\"{$value}\"") . "\n";
        }
    }
    echo "\n";

    // Show how we skip first 4 columns
    echo "=== AFTER SKIPPING FIRST 4 COLUMNS ===\n";
    if (isset($rows[1])) {
        $data = array_slice($rows[1], 4); // Skip first 4 columns
        echo "Remaining columns (starting from Column 5):\n";
        foreach ($data as $index => $value) {
            $originalColumn = $index + 5; // Original column number
            $columnLetter = chr(65 + $index + 4); // Column letter after skipping
            echo "data[" . $index . "] (Column {$originalColumn}, {$columnLetter}): " . (empty($value) ? '[EMPTY]' : "\"{$value}\"") . "\n";
        }
    }
    echo "\n";

    // Show current mapping
    echo "=== CURRENT MAPPING ===\n";
    if (isset($rows[1])) {
        $data = array_slice($rows[1], 4);

        $mapping = [
            'product_code' => $data[0] ?? null, // Column 5
            'product_name' => $data[1] ?? null, // Column 6
            'supplier_code' => $data[2] ?? null, // Column 7
            'isku' => $data[10] ?? null, // Column 16
            'cost' => $data[13] ?? null, // Column 18
            'rrp' => $data[12] ?? null, // Column 19
            'diameter' => $data[4] ?? null, // Column 10
            'length' => $data[5] ?? null, // Column 11
            'width' => $data[6] ?? null, // Column 12
            'covered_area' => $data[7] ?? null, // Column 13
            'thickness' => $data[8] ?? null, // Column 14
            'watt_m2' => $data[9] ?? null, // Column 15
            'ip_class' => $data[15] ?? null, // Column 20
            'cold_lead' => $data[16] ?? null, // Column 21
            'cold_lead_length' => $data[17] ?? null, // Column 22
            'outside_jacket_material' => $data[18] ?? null, // Column 23
            'inside_jacket_material' => $data[19] ?? null, // Column 24
            'certificates' => $data[20] ?? null, // Column 25
            'voltage' => $data[21] ?? null, // Column 26
            'total_wattage' => $data[22] ?? null, // Column 27
            'amp' => $data[23] ?? null, // Column 28
            'categories' => $data[24] ?? null, // Column 29
            'sub_categories' => $data[25] ?? null, // Column 30
            'product_line' => $data[26] ?? null, // Column 31
            'tags' => $data[27] ?? null, // Column 33
            'item_tags' => $data[28] ?? null, // Column 34
            'fire_retardent' => $data[29] ?? null, // Column 32
            'product_warranty' => $data[35] ?? null, // Column 41
            'self_adhesive' => $data[36] ?? null, // Column 42
            'includes' => $data[37] ?? null, // Column 45
            'related_products' => $data[38] ?? null, // Column 46
            'product_item_code' => $data[2] ?? null, // Column 8 - THIS IS THE PROBLEM!
            'title' => $data[11] ?? null, // Column 17
            'short_desc' => $data[30] ?? null, // Column 35
        ];

        foreach ($mapping as $field => $value) {
            echo "{$field}: " . (empty($value) ? '[EMPTY]' : "\"{$value}\"") . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error reading Excel file: " . $e->getMessage() . "\n";
}
