<?php

// Simple test to verify file sorting logic
$files = [
    'products/BDB/BDB_1.jpg',
    'products/BDB/BDB_2.jpg', 
    'products/BDB/BDB_3.jpg',
    'products/BDB/BDB_4.jpg',
    'products/BDB/BDB_Main.jpg'
];

$productCode = 'BDB';

echo "Original file order:\n";
foreach ($files as $file) {
    echo basename($file) . "\n";
}

echo "\nSorting files to prioritize _Main images:\n";

// Sort files to prioritize _Main images first
usort($files, function($a, $b) use ($productCode) {
    $filenameA = pathinfo($a, PATHINFO_FILENAME);
    $filenameB = pathinfo($b, PATHINFO_FILENAME);
    
    $isMainA = preg_match('/^' . preg_quote($productCode, '/') . '_Main$/', $filenameA);
    $isMainB = preg_match('/^' . preg_quote($productCode, '/') . '_Main$/', $filenameB);
    
    // If A is _Main and B is not, A comes first
    if ($isMainA && !$isMainB) return -1;
    // If B is _Main and A is not, B comes first  
    if (!$isMainA && $isMainB) return 1;
    
    // If both are _Main or both are not _Main, sort by filename
    return strcmp($filenameA, $filenameB);
});

echo "Sorted file order:\n";
foreach ($files as $file) {
    echo basename($file) . "\n";
}

echo "\nFirst file (should be BDB_Main.jpg):\n";
echo basename($files[0]) . "\n";

if (basename($files[0]) === 'BDB_Main.jpg') {
    echo "✅ SUCCESS: _Main image is prioritized!\n";
} else {
    echo "❌ ISSUE: _Main image is not prioritized\n";
}