<?php

/**
 * Script to ensure all required storage directories exist
 * This can be run during deployment to ensure directories are created
 */

require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\Storage;

$directories = [
    'categories',
    'excel',
    'images',
    'product-documents',
    'products',
];

echo "Ensuring storage directories exist...\n";

foreach ($directories as $directory) {
    $path = 'public/' . $directory;
    
    if (!Storage::disk('local')->exists($path)) {
        Storage::disk('local')->makeDirectory($path, 0755, true);
        echo "Created directory: {$path}\n";
    } else {
        echo "Directory exists: {$path}\n";
    }
}

echo "All required storage directories are ready.\n";