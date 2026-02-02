#!/bin/bash

# Script to ensure all required storage directories exist
# This can be run during deployment to ensure directories are created

echo "Ensuring storage directories exist..."

# Create directories if they don't exist
mkdir -p storage/app/public/categories
mkdir -p storage/app/public/excel
mkdir -p storage/app/public/images
mkdir -p storage/app/public/product-documents
mkdir -p storage/app/public/products

echo "Setting proper permissions..."
chmod -R 755 storage/app/public

echo "All required storage directories are ready."
echo "Directories created:"
echo "  - storage/app/public/categories"
echo "  - storage/app/public/excel"
echo "  - storage/app/public/images"
echo "  - storage/app/public/product-documents"
echo "  - storage/app/public/products"