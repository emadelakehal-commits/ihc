# IHC Product Management API Documentation

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
All endpoints are currently public (no authentication required).

---

## 1. Create Product

**Endpoint:** `POST /products`  
**Purpose:** Create a new product with translations

**Request Body:**
```json
{
  "mainProductCode": "WHPL",
  "translations": [
    {
      "language": "en",
      "title": "Workstation Footboards",
      "summary": "Our workstation heating footboards are designed as a smart, energy-saving alternative to heating entire industrial or commercial spaces.",
      "description": "<h2>Plug-and-Play Comfort with Ultra-Low Energy Consumption</h2><p>Each footboard is fully plug-and-play...</p>"
    },
    {
      "language": "de",
      "title": "Arbeitsplatz-Fußböden",
      "summary": "Unsere Arbeitsplatz-Heizfußböden sind als intelligente, energiesparende Alternative zum Heizen ganzer industrieller oder kommerzieller Räume konzipiert.",
      "description": "<h2>Plug-and-Play-Komfort mit extrem niedrigem Energieverbrauch</h2><p>Jedes Fußbrett ist vollständig plug-and-play...</p>"
    }
  ]
}
```

**Note:** API parameter names remain unchanged for backward compatibility. The `mainProductCode` parameter is internally mapped to the `main_product_code` database column.

**Parameters:**
- `mainProductCode` (string, required): Unique main product code (max 100 chars)
- `translations` (array, required): Array of translation objects
  - `language` (string, required): Language code (must exist in lkp_language table)
  - `title` (string, required): Product title (max 255 chars)
  - `summary` (string, optional): Product summary
  - `description` (string, optional): Product description (HTML allowed)

**Response (201):**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "main_product_code": "WHPL",
    "created_at": "2025-12-17T17:00:20.000000Z",
    "updated_at": "2025-12-17T17:00:20.000000Z",
    "translations": [
      {
        "product_code": "WHPL",
        "language": "en",
        "title": "Workstation Footboards",
        "summary": "Our workstation heating...",
        "description": "<h2>Plug-and-Play Comfort...</h2>"
      }
    ]
  }
}
```

---

## 2. Create Product Variants

**Endpoint:** `POST /products/{mainProductCode}/variants`  
**Purpose:** Create product variants under a main product

**Request Body:**
```json
{
  "products": [
    {
      "productCode": "WHPL-180-120",
      "isku": "WHPL-180-120-SKU",
      "isActive": true,
      "cost": 840.00,
      "costCurrency": "EUR",
      "rrp": 840.00,
      "rrpCurrency": "EUR",
      "translations": [
        {
          "language": "en",
          "title": "Heating Mat 180x120cm",
          "short_desc": "Professional heating mat for industrial use"
        },
        {
          "language": "lt",
          "title": "Šildymo danga 180x120cm",
          "short_desc": "Profesionali šildymo danga pramoniniam naudojimui"
        }
      ],
      "categories": ["electronics", "laptops"],
      "attributes": {
        "en": [
          {"name": "cable", "value": "Double"},
          {"name": "colour", "value": "Black"},
          {"name": "length", "value": "1200"},
          {"name": "diameter", "value": "8"},
          {"name": "covered_area", "value": "18"},
          {"name": "cold_lead", "value": "Yes"},
          {"name": "cold_lead_length", "value": "2"},
          {"name": "out_side_jacket_material", "value": "PVC"},
          {"name": "inside_jacket_material", "value": "Copper"},
          {"name": "certificates", "value": "CE, RoHS"},
          {"name": "voltage", "value": "230"},
          {"name": "total_wattage", "value": "1530"},
          {"name": "watt_m2", "value": "85"},
          {"name": "amp", "value": "6.7"},
          {"name": "fire_retardent", "value": "Yes"},
          {"name": "product_warranty", "value": "10 Years"},
          {"name": "self_adhesive", "value": "No"},
          {"name": "includes", "value": "Heating mat, installation guide"},
          {"name": "other", "value": "Power consumption is 400 w/m2"},
          {"name": "plug", "value": "Yes"},
          {"name": "power", "value": "850"},
          {"name": "thickness", "value": "20"},
          {"name": "warranty", "value": "10 Years"},
          {"name": "weight", "value": "10000"},
          {"name": "width", "value": "1800"}
        ],
        "de": [
          {"name": "cable", "value": "Doppelt"},
          {"name": "colour", "value": "Schwarz"},
          {"name": "length", "value": "1200mm"},
          {"name": "diameter", "value": "8mm"},
          {"name": "covered_area", "value": "18m²"},
          {"name": "cold_lead", "value": "Ja"},
          {"name": "cold_lead_length", "value": "2m"},
          {"name": "out_side_jacket_material", "value": "PVC"},
          {"name": "inside_jacket_material", "value": "Kupfer"},
          {"name": "certificates", "value": "CE, RoHS"},
          {"name": "voltage", "value": "230V"},
          {"name": "total_wattage", "value": "1530W"},
          {"name": "watt_m2", "value": "85W/m²"},
          {"name": "amp", "value": "6.7A"},
          {"name": "fire_retardent", "value": "Ja"},
          {"name": "product_warranty", "value": "10 Jahre"},
          {"name": "self_adhesive", "value": "Nein"},
          {"name": "includes", "value": "Heizmatte, Installationsanleitung"},
          {"name": "other", "value": "Leistungsaufnahme beträgt 400 w/m2"},
          {"name": "plug", "value": "Ja"},
          {"name": "power", "value": "850W"},
          {"name": "thickness", "value": "20mm"},
          {"name": "warranty", "value": "10 Jahre"},
          {"name": "weight", "value": "10000g"},
          {"name": "width", "value": "1800mm"}
        ]
      },
      "deliveries": {
        "LT": {"min": 2, "max": 3},
        "EU": {"min": 5, "max": 7}
      },
      "documents": [
        {"type": "manual", "url": "assets/documents/manual.pdf"},
        {"type": "technical", "url": "assets/documents/specs.pdf"}
      ],
      "tags": ["bestseller", "new_arrival", "eco_friendly"]
    }
  ]
}
```

**Parameters:**
- `products` (array, required): Array of product objects
  - `productItemCode` (string, required): Product item code (max 100 chars, can be repeated)
  - `isku` (string, required): International Stock Keeping Unit (max 100 chars, unique primary key)
  - `isActive` (boolean, required): Product active status
  - `cost` (number, optional): Cost price
  - `costCurrency` (string, optional): Currency code (3 chars, must exist in lkp_currency)
  - `rrp` (number, optional): Recommended retail price
  - `rrpCurrency` (string, optional): RRP currency code (3 chars, must exist in lkp_currency)
  - `translations` (array, required): Array of translation objects for product item
    - `language` (string, required): Language code (must exist in lkp_language)
    - `title` (string, required): Product title (max 255 chars)
    - `short_desc` (string, optional): Short description
  - `categories` (array, optional): Array of category codes (must exist in lkp_category)
  - `attributes` (object, optional): Attributes grouped by language
    - Language keys (e.g., "en", "de") containing arrays of attribute objects
      - `name` (string, required): Attribute name
      - `value` (string, required): Attribute value
  - `deliveries` (object, optional): Delivery information by domain
    - Domain keys (e.g., "LT", "EU") containing delivery objects
      - `min` (integer, required): Minimum delivery days
      - `max` (integer, required): Maximum delivery days
  - `documents` (array, optional): Array of document objects
    - `type` (string, required): Document type ("manual", "technical", "warranty")
    - `url` (string, required): Document URL (max 500 chars)
  - `tags` (array, optional): Array of tag codes (must exist in lkp_tag)
  - `itemTags` (array, optional): Array of item tag codes (must exist in lkp_item_tag)

**Response (201):**
```json
{
  "success": true,
  "message": "Products created successfully",
  "data": [
    {
      "product_item_code": "WHPL-180-120",
      "product_code": "WHPL",
      "is_active": true,
      "cost": "840.00",
      "cost_currency": "EUR",
      "rrp": "840.00",
      "rrp_currency": "EUR",
      "created_at": "2025-12-17T17:00:20.000000Z",
      "updated_at": "2025-12-17T17:00:20.000000Z",
      "categories": [
        {"category_code": "electronics", ...},
        {"category_code": "laptops", ...}
      ],
      "attribute_values": [...],
      "deliveries": [...],
      "documents": [...]
    }
  ]
}
```

---

## 3. Get Product

**Endpoint:** `GET /products/{mainProductCode}`  
**Purpose:** Retrieve product with all variants

**Query Parameters:**
- `language` (string, optional): Language code for translations and attributes (default: "en")

**Example:** `GET /products/WHPL?language=en`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "product_code": "WHPL",
    "created_at": "2025-12-17T17:00:20.000000Z",
    "updated_at": "2025-12-17T17:00:20.000000Z",
    "images": [
      "http://127.0.0.1:8000/storage/products/WHPL/main/cover.jpg",
      "http://127.0.0.1:8000/storage/products/WHPL/main/back.jpg"
    ],
    "translations": [
      {
        "language": "en",
        "title": "Workstation Footboards",
        "summary": "Our workstation heating...",
        "description": "<h2>Plug-and-Play Comfort...</h2>"
      }
    ],
    "tags": [
      {"tag_code": "HEATING", "tag_name": "Heating"},
      {"tag_code": "INDUSTRIAL", "tag_name": "Industrial"}
    ],
    "products": [
      {
        "product_item_code": "WHPL-180-120",
        "is_active": true,
        "cost": "840.00",
        "cost_currency": "EUR",
        "rrp": "840.00",
        "rrp_currency": "EUR",
        "availability": "s",
        "images": [
          "http://127.0.0.1:8000/storage/products/WHPL/variants/WHPL-180-120/1.jpg"
        ],
        "categories": ["electronics", "laptops"],
        "attributes": [
          {"name": "cable", "value": "Double"},
          {"name": "colour", "value": "Black"},
          {"name": "length", "value": "1200"},
          {"name": "other", "value": "Power consumption is 400 w/m2"},
          {"name": "plug", "value": "Yes"},
          {"name": "power", "value": "850"},
          {"name": "thickness", "value": "20"},
          {"name": "warranty", "value": "10 Years"},
          {"name": "weight", "value": "10000"},
          {"name": "width", "value": "1800"}
        ],
        "deliveries": [
          {"domain": "LT", "min": 2, "max": 3}
        ],
        "documents": [
          {"type": "manual", "url": "assets/documents/manual.pdf"}
        ]
      }
    ]
  }
}
```

---

## 4. Get Product Details

**Endpoint:** `GET /products/{productCode}/details`  
**Purpose:** Retrieve individual product details

**Query Parameters:**
- `language` (string, optional): Language code for attributes (default: "en")

**Example:** `GET /products/WHPL-180-120/details?language=en`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "product_item_code": "WHPL-180-120",
    "is_active": true,
    "created_at": "2025-12-17T17:00:20.000000Z",
    "updated_at": "2025-12-17T17:00:20.000000Z",
    "cost": "840.00",
    "cost_currency": "EUR",
    "rrp": "840.00",
    "rrp_currency": "EUR",
    "availability": "s",
    "product_code": "WHPL",
    "images": [
      "http://127.0.0.1:8000/storage/products/WHPL/variants/WHPL-180-120/1.jpg"
    ],
    "categories": ["electronics", "laptops"],
    "attributes": [
      {"name": "cable", "value": "Double"},
      {"name": "colour", "value": "Black"},
      {"name": "length", "value": "1200"},
      {"name": "other", "value": "Power consumption is 400 w/m2"},
      {"name": "plug", "value": "Yes"},
      {"name": "power", "value": "850"},
      {"name": "thickness", "value": "20"},
      {"name": "warranty", "value": "10 Years"},
      {"name": "weight", "value": "10000"},
      {"name": "width", "value": "1800"}
    ],
    "deliveries": [
      {"domain": "LT", "min": 2, "max": 3}
    ],
    "documents": [
      {"type": "manual", "url": "assets/documents/manual.pdf"}
    ],
    "tags": [
      {"tag_code": "BESTSELLER", "tag_name": "Bestseller"},
      {"tag_code": "ECO_FRIENDLY", "tag_name": "Eco Friendly"}
    ]
  }
}
```

---

## 5. Update Product

**Endpoint:** `PUT /products/{mainProductCode}`  
**Purpose:** Update product translations

**Request Body:**
```json
{
  "translations": [
    {
      "language": "en",
      "title": "Updated Workstation Footboards",
      "summary": "Updated summary",
      "description": "Updated description"
    },
    {
      "language": "de",
      "title": "Aktualisierte Arbeitsplatz-Fußböden",
      "summary": "Aktualisierte Zusammenfassung",
      "description": "Aktualisierte Beschreibung"
    }
  ]
}
```

**Parameters:**
- `translations` (array, required): Array of translation objects
  - `language` (string, required): Language code (must exist in lkp_language)
  - `title` (string, required): Updated title (max 255 chars)
  - `summary` (string, optional): Updated summary
  - `description` (string, optional): Updated description

**Response (200):**
```json
{
  "success": true,
  "message": "Product translations updated successfully",
  "data": {
    "product_code": "WHPL",
    "translations": [
      {
        "product_code": "WHPL",
        "language": "en",
        "title": "Updated Workstation Footboards",
        "summary": "Updated summary",
        "description": "Updated description"
      }
    ]
  }
}
```

---

## 6. Update Product

**Endpoint:** `PUT /products/{productCode}/update`  
**Purpose:** Update individual product information

**Request Body:**
```json
{
  "isActive": true,
  "cost": 800.00,
  "costCurrency": "EUR",
  "rrp": 800.00,
  "rrpCurrency": "EUR",
  "categories": ["electronics", "laptops"],
  "attributes": {
    "en": [
      {"name": "cable", "value": "Double"},
      {"name": "colour", "value": "Black"},
      {"name": "length", "value": "1200"},
      {"name": "diameter", "value": "8"},
      {"name": "covered_area", "value": "18"},
      {"name": "cold_lead", "value": "Yes"},
      {"name": "cold_lead_length", "value": "2"},
      {"name": "out_side_jacket_material", "value": "PVC"},
      {"name": "inside_jacket_material", "value": "Copper"},
      {"name": "certificates", "value": "CE, RoHS"},
      {"name": "voltage", "value": "230"},
      {"name": "total_wattage", "value": "1530"},
      {"name": "watt_m2", "value": "85"},
      {"name": "amp", "value": "6.7"},
      {"name": "fire_retardent", "value": "Yes"},
      {"name": "product_warranty", "value": "10 Years"},
      {"name": "self_adhesive", "value": "No"},
      {"name": "includes", "value": "Heating mat, installation guide"},
      {"name": "other", "value": "Power consumption is 400 w/m2"},
      {"name": "plug", "value": "Yes"},
      {"name": "power", "value": "850"},
      {"name": "thickness", "value": "20"},
      {"name": "warranty", "value": "10 Years"},
      {"name": "weight", "value": "10000"},
      {"name": "width", "value": "1800"}
    ],
    "de": [
      {"name": "cable", "value": "Doppelt"},
      {"name": "colour", "value": "Schwarz"},
      {"name": "length", "value": "1200mm"},
      {"name": "diameter", "value": "8mm"},
      {"name": "covered_area", "value": "18m²"},
      {"name": "cold_lead", "value": "Ja"},
      {"name": "cold_lead_length", "value": "2m"},
      {"name": "out_side_jacket_material", "value": "PVC"},
      {"name": "inside_jacket_material", "value": "Kupfer"},
      {"name": "certificates", "value": "CE, RoHS"},
      {"name": "voltage", "value": "230V"},
      {"name": "total_wattage", "value": "1530W"},
      {"name": "watt_m2", "value": "85W/m²"},
      {"name": "amp", "value": "6.7A"},
      {"name": "fire_retardent", "value": "Ja"},
      {"name": "product_warranty", "value": "10 Jahre"},
      {"name": "self_adhesive", "value": "Nein"},
      {"name": "includes", "value": "Heizmatte, Installationsanleitung"},
      {"name": "other", "value": "Leistungsaufnahme beträgt 400 w/m2"},
      {"name": "plug", "value": "Ja"},
      {"name": "power", "value": "850W"},
      {"name": "thickness", "value": "20mm"},
      {"name": "warranty", "value": "10 Jahre"},
      {"name": "weight", "value": "10000g"},
      {"name": "width", "value": "1800mm"}
    ]
  },
  "deliveries": {
    "LT": {"min": 2, "max": 3},
    "EU": {"min": 5, "max": 7}
  },
  "documents": [
    {"type": "manual", "url": "assets/documents/updated-manual.pdf"}
  ]
}
```

**Parameters (All Optional):**
- `isActive` (boolean, optional): Product active status
- `cost` (number, optional): Updated cost price
- `costCurrency` (string, optional): Currency code (3 chars)
- `rrp` (number, optional): Updated RRP
- `rrpCurrency` (string, optional): RRP currency code (3 chars)
- `categories` (array, optional): Array of category codes
- `attributes` (object, optional): Attributes grouped by language
  - **Update Logic**: For each attribute-language combination:
    - If the combination exists for this product → **Update** the value
    - If the combination doesn't exist → **Add** new attribute record
- `deliveries` (object, optional): Delivery information by domain
- `documents` (array, optional): Array of document objects
  - `tags` (array, optional): Array of tag codes (must exist in lkp_tag)
  - `itemTags` (array, optional): Array of item tag codes (must exist in lkp_item_tag)

**Note:** Since this is an update operation, you can provide only the fields you want to update. All fields are optional.

**Attribute Update Behavior:**
- **Existing combinations**: Values are updated in place
- **New combinations**: New records are created
- **No duplicates**: Same attribute-language pairs are never duplicated

**Response (200):**
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "product_item_code": "WHPL-180-120",
    "is_active": true,
    "cost": "800.00",
    "cost_currency": "EUR",
    "rrp": "800.00",
    "rrp_currency": "EUR",
    "categories": [...],
    "attribute_values": [...],
    "deliveries": [...],
    "documents": [...]
  }
}
```

---

## 7. Upload Product Images

**Endpoint:** `POST /products/{mainProductCode}/images`
**Purpose:** Upload images for product
**Content-Type:** `multipart/form-data`

**Request Body (Form Data):**
- `images[]` (file array, required): Multiple image files

**Example curl:**
```bash
curl -X POST http://127.0.0.1:8000/api/products/WHPL/images \
  -F "images[]=@cover.jpg" \
  -F "images[]=@back.jpg"
```

**Response (201):**
```json
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": [
    {
      "filename": "1734782790_cover.jpg",
      "url": "http://127.0.0.1:8000/storage/products/WHPL/main/1734782790_cover.jpg",
      "path": "products/WHPL/main/1734782790_cover.jpg"
    }
  ]
}
```

---

## 8. Upload Product Images

**Endpoint:** `POST /products/{productCode}/images`  
**Purpose:** Upload images for product variant  
**Content-Type:** `multipart/form-data`

**Request Body (Form Data):**
- `images[]` (file array, required): Multiple image files

**Example curl:**
```bash
curl -X POST http://127.0.0.1:8000/api/products/WHPL-180-120/images \
  -F "images[]=@product1.jpg" \
  -F "images[]=@product2.jpg"
```

**Response (201):**
```json
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": [
    {
      "filename": "1734782800_product1.jpg",
      "url": "http://127.0.0.1:8000/storage/products/WHPL/variants/WHPL-180-120/1734782800_product1.jpg",
      "path": "products/WHPL/variants/WHPL-180-120/1734782800_product1.jpg"
    }
  ]
}
```

---

## 9. Get Category Details

**Endpoint:** `POST /categories/details`  
**Purpose:** Retrieve category information for an array of category codes

**Request Body:**
```json
{
  "categories": ["books", "electronics", "laptops"],
  "lang": "en"
}
```

**Parameters:**
- `categories` (array, required): Array of category codes (must exist in lkp_category table)
- `lang` (string, required): Language code for category names (must exist in lkp_language table, 2 characters)

**Example Request:**
```bash
curl -X POST "http://127.0.0.1:8000/api/categories/details" \
  -H "Content-Type: application/json" \
  -d '{
    "categories": ["books", "electronics", "laptops"],
    "lang": "en"
  }'
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "category_code": "books",
      "parent_codes": [],
      "name": "Books",
      "image_url": "http://127.0.0.1:8000/storage/categories/books.jpg"
    },
    {
      "category_code": "electronics",
      "parent_codes": [],
      "name": "Electronics",
      "image_url": "none"
    },
    {
      "category_code": "laptops",
      "parent_codes": ["electronics"],
      "name": "Laptops",
      "image_url": "none"
    }
  ]
}
```

**Lithuanian Example:**
```bash
curl -X POST "http://127.0.0.1:8000/api/categories/details" \
  -H "Content-Type: application/json" \
  -d '{
    "categories": ["books", "electronics"],
    "lang": "lt"
  }'
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "category_code": "books",
      "parent_codes": [],
      "name": "Knygos",
      "image_url": "http://127.0.0.1:8000/storage/categories/books.jpg"
    },
    {
      "category_code": "electronics",
      "parent_codes": [],
      "name": "Elektronika",
      "image_url": "none"
    }
  ]
}
```

**Image Storage:**
- Images are stored as: `storage/app/public/categories/{categorycode}.jpg` or `storage/app/public/categories/{categorycode}.png`
- The API checks for both .jpg and .png extensions (uses the first one found)
- URLs are automatically generated when the image file exists
- Returns `"none"` if no image file is found

**Validation Errors (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "categories": ["The selected categories.0 is invalid."],
    "lang": ["The selected lang is invalid."]
  }
}
```

---

## Error Responses

All endpoints return errors in this format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `404` - Not Found
- `422` - Validation Failed
- `500` - Internal Server Error

---

## Data Types and Constraints

**Language Codes:** Must exist in `lkp_language.code` table  
**Currency Codes:** Must exist in `lkp_currency.code` table (3 characters)  
**Category Codes:** Must exist in `lkp_category.category_code` table  
**Domain Codes:** Must exist in `lkp_domain.code` table  
**Document Types:** "manual", "technical", "warranty"  
**Image Formats:** jpeg, png, jpg, gif (max 2MB each)  
**String Lengths:** As specified in parameter descriptions

---

## Image Storage Structure

Images are automatically organized in this folder structure:
```
storage/app/public/products/
├── {mainProductCode}/
│   ├── main/
│   │   ├── cover.jpg
│   │   └── back.jpg
│   └── variants/
│       ├── {productCode1}/
│       │   ├── 1.jpg
│       │   └── 2.jpg
│       └── {productCode2}/
│           └── 1.jpg
```

All images are accessible via public URLs and only image files are returned in API responses.

---

## Database Schema

### Main Product Tables
- `product` - Main product information
- `product_translation` - Multi-language translations for main products

### Product Tables
- `product_item` - Product variant information (primary key: isku, product_item_code can be repeated)
- `product_item_translations` - Multi-language translations for product items (foreign key: isku)
- `product_attribute_value` - Product attributes with language support
- `product_category` - Product category relationships
- `product_delivery` - Delivery information by domain
- `product_document` - Product documents

### Lookup Tables
- `lkp_language` - Available languages
- `lkp_currency` - Available currencies
- `lkp_category` - Product categories
- `lkp_category_translations` - Multi-language category names
- `lkp_attribute` - Available attributes
- `lkp_domain` - Delivery domains
- `lkp_tag` - Product tags
- `lkp_tag_translations` - Multi-language tag names
- `lkp_item_tag` - Product item tags
- `lkp_item_tag_translations` - Multi-language item tag names

### Relationship Tables
- `product_tag` - Product-tag relationships (many-to-many)
- `product_item_tag` - Product item-tag relationships (many-to-many)

---

## 10. Process Excel File

**Endpoint:** `GET /process-excel`  
**Purpose:** Process Excel file to create products with all relationships, handling dimension conversions and related product mappings

**Query Parameters:**
- `file` (string, required): Excel filename located in `storage/app/public/excel/` directory
- `lang` (string, required): Language code for translations (must exist in lkp_language table, 2 characters)

**Example:** `GET /process-excel?file=products.xlsx&lang=en`

**Excel File Structure:**
- **Ignores first 4 columns:** Total, Recommended Qty, Stock/On demand, Cost
- **Processes columns 5-46** according to the mapping specification
- **Dimensions (diameter, thickness, length, width)** are converted from meters to millimeters
- **Related products** are mapped from product names to product codes

**Column Mapping:**
- Column 5: Product Code
- Column 6: Product name
- Column 7: Supplier Product Item Code
- Column 8: (unused)
- Column 9: (unused)
- Column 10: Diameter (m) → converted to mm
- Column 11: Length (m) → converted to mm
- Column 12: Width (m) → converted to mm
- Column 13: Covered Area (m2)
- Column 14: Thickness (m) → converted to mm
- Column 15: Watt/M2
- Column 16: ISKU
- Column 17: Product item name
- Column 18: RRP (Eur) → used as cost
- Column 19: Cost (Net Price EUR) → used as RRP
- Column 20: IP Class
- Column 21: Cold Lead
- Column 22: Cold Lead Length
- Column 23: Outside Jacket Material
- Column 24: Inside Jacket Material
- Column 25: Certificates
- Column 26: Voltage (V)
- Column 27: Total Wattage (W)
- Column 28: Amp (A)
- Column 29: Product Cats (comma-separated)
- Column 30: Product sub Cat1 (comma-separated)
- Column 31: Product Line
- Column 32: Fire-retardent
- Column 33: Product tags (comma-separated)
- Column 34: Product item tags (comma-separated)
- Column 35: Product Item Short Description
- Column 36-40: (unused)
- Column 41: Product Warranty
- Column 42: Self adhesive
- Column 43-44: (unused)
- Column 45: Includes
- Column 46: Related Products (comma-separated product names → mapped to product codes)

**Dimension Conversion Logic:**
- Values with 'mm' suffix: kept as-is
- Values with 'm' suffix: multiplied by 1000
- Values without unit: assumed to be meters, multiplied by 1000

**Related Products Processing:**
- Product names from Excel are mapped to product codes
- Bidirectional many-to-many relationships are created
- Relationships are stored in `product_related` table

**Response (200):**
```json
{
  "success": true,
  "message": "Excel file processed successfully",
  "data": {
    "processed_products": 25,
    "file": "products.xlsx",
    "language": "en"
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "Excel file not found"
}
```

**Error Response (500):**
```json
{
  "success": false,
  "message": "Failed to process Excel file",
  "error": "Detailed error message"
}
```

**Notes:**
- Excel files must be placed in `storage/app/public/excel/` directory (preferred) or `storage/app/public/` directory
- The API will automatically search both locations for the specified file
- All database operations are wrapped in transactions for data integrity
- Related products are processed after all products are created to ensure proper mapping
- Dimension values are stored in millimeters in the database
- Product names from the related products column are automatically mapped to product codes

---

## Database Schema Updates

### Relationship Tables
- `product_related` - Flexible entity relationships (product-to-product, product-to-product_item, etc.)
  - `id` (BIGINT, PRIMARY KEY): Auto-incrementing primary key
  - `from_entity_type` (ENUM: 'product', 'product_item'): Type of the source entity
  - `from_entity_code` (VARCHAR): Code of the source entity (product_code for products, isku for product_items)
  - `to_entity_type` (ENUM: 'product', 'product_item'): Type of the target entity
  - `to_entity_code` (VARCHAR): Code of the target entity (product_code for products, isku for product_items)
  - `relation_type` (VARCHAR, optional): Type of relationship (e.g., 'related', 'bundle', 'accessory')
  - `created_at` (TIMESTAMP): Creation timestamp
  - Indexes on (from_entity_type, from_entity_code) and (to_entity_type, to_entity_code) for performance

---

## 11. Get Related Products

**Endpoint:** `GET /related-products`  
**Purpose:** Get related products/items for a given product or product item, formatted for display in related product forms/boxes

**Query Parameters:**
- `entity_code` (string, required): Either a product_code or product item isku
- `lang` (string, required): Language code for titles (must exist in lkp_language table, 2 characters)

**Examples:**
- `GET /related-products?entity_code=ABC123&lang=en` - Get related products for product ABC123
- `GET /related-products?entity_code=XYZ789&lang=de` - Get related products for product item XYZ789

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "type": "product",
      "code": "ABC123",
      "image_url": "http://127.0.0.1:8000/storage/products/ABC123/main/image.jpg",
      "cost": null,
      "cost_currency": null,
      "rrp": null,
      "rrp_currency": null,
      "title": "Product Title"
    },
    {
      "type": "product_item",
      "isku": "XYZ789",
      "image_url": "http://127.0.0.1:8000/storage/products/ABC123/product-items/XYZ789/image.jpg",
      "cost": 100.50,
      "cost_currency": "EUR",
      "rrp": 150.00,
      "rrp_currency": "EUR",
      "title": "Product Item Title"
    }
  ]
}
```

**Response Fields:**
- `type` (string): Either "product" or "product_item"
- `code` (string): Product code (only for products)
- `isku` (string): Product item ISKU (only for product items)
- `product_code` (string): Parent product code (only for product items)
- `image_url` (string): URL of the first available product image, or null if no image exists
- `cost` (number): Cost price (null for products, actual value for product items)
- `cost_currency` (string): Cost currency code (null for products, "EUR" for product items)
- `rrp` (number): Recommended retail price (null for products, actual value for product items)
- `rrp_currency` (string): RRP currency code (null for products, "EUR" for product items)
- `title` (string): Product/item title in the specified language

**Error Response (404):**
```json
{
  "success": false,
  "message": "Entity not found"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "entity_code": ["The entity code field is required."],
    "lang": ["The selected lang is invalid."]
  }
}
```

**Notes:**
- Automatically detects whether the `entity_code` is a product or product item
- Returns bidirectional relationships (both directions of the relationship)
- Finds the first available image in the appropriate directory structure
- Products don't have individual cost/RRP (returns null), only product items do
- Titles are returned in the specified language from translation tables

---

## 12. Get Sub-Categories

**Endpoint:** `GET /categories/sub-categories`  
**Purpose:** Get sub-categories for a given category code, formatted as cards for display

**Query Parameters:**
- `category_code` (string, required): Category code to get sub-categories for (must exist in lkp_category table)
- `lang` (string, required): Language code for category names (must exist in lkp_language table, 2 characters)

**Examples:**
- `GET /categories/sub-categories?category_code=FLOOR_HEATING&lang=en` - Get sub-categories for Floor Heating
- `GET /categories/sub-categories?category_code=INSULATION&lang=de` - Get sub-categories for Insulation in German

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "name": "Tiles - Stone - Screed",
      "code": "TILES_STONE_SCREED",
      "has_sub_categories": false,
      "image_url": "http://127.0.0.1:8000/storage/categories/TILES_STONE_SCREED.jpg"
    },
    {
      "name": "Laminate - Wooden - Floating",
      "code": "LAMINATE_WOODEN_FLOATING",
      "has_sub_categories": false,
      "image_url": null
    }
  ]
}
```

**Response Fields:**
- `name` (string): Category name in the specified language (falls back to category_code if no translation exists)
- `code` (string): Category code
- `has_sub_categories` (boolean): Whether this category has its own sub-categories
- `image_url` (string): URL of the category image, or null if no image exists

**Error Response (404):**
```json
{
  "success": false,
  "message": "Entity not found"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "category_code": ["The selected category_code is invalid."],
    "lang": ["The selected lang is invalid."]
  }
}
```

**Notes:**
- Returns only direct children (sub-categories) of the specified category
- Images are stored as `storage/app/public/categories/{category_code}.{extension}`
- Supports multiple image formats: jpg, jpeg, png, gif, bmp, webp
- `has_sub_categories` indicates if the category can be further expanded
- Category names are translated based on the specified language

---

## 13. Get Products by Categories

**Endpoint:** `POST /products/by-categories`  
**Purpose:** Get product items that belong to one or more specified categories

**Request Body:**
```json
{
  "category_codes": ["FLOOR_HEATING", "INSULATION"],
  "lang": "en",
  "page": 1,
  "per_page": 20
}
```

**Parameters:**
- `category_codes` (array, required): Array of category codes to filter products by (must exist in lkp_category table)
- `lang` (string, required): Language code for product item names (must exist in lkp_language table, 2 characters)
- `page` (integer, optional): Page number for pagination (default: 1, min: 1)
- `per_page` (integer, optional): Number of items per page (default: 20, min: 1, max: 100)

**Examples:**
- Get products from Floor Heating category in English
- Get products from multiple categories (Floor Heating and Insulation) in German
- Get paginated results (page 2, 10 items per page)

**Response (200) - Without Pagination:**
```json
{
  "success": true,
  "data": [
    {
      "product_item_code": "WHPL-180-120",
      "isku": "WHPL-180-120-SKU",
      "image": "http://127.0.0.1:8000/storage/products/WHPL/variants/WHPL-180-120/image.jpg",
      "cost": 840.50,
      "cost_currency": "EUR",
      "rrp": 950.00,
      "rrp_currency": "EUR",
      "product_item_name": "Heating Mat 180x120cm"
    }
  ]
}
```

**Response (200) - With Pagination:**
```json
{
  "success": true,
  "data": [
    {
      "product_item_code": "WHPL-180-120",
      "isku": "WHPL-180-120-SKU",
      "image": "http://127.0.0.1:8000/storage/products/WHPL/variants/WHPL-180-120/image.jpg",
      "cost": 840.50,
      "cost_currency": "EUR",
      "rrp": 950.00,
      "rrp_currency": "EUR",
      "product_item_name": "Heating Mat 180x120cm"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3,
    "from": 1,
    "to": 20,
    "has_more_pages": true,
    "prev_page_url": null,
    "next_page_url": "http://127.0.0.1:8000/api/products/by-categories?page=2"
  }
}
```

**Response Fields:**
- `product_item_code` (string): Product item code
- `isku` (string): International Stock Keeping Unit (unique identifier)
- `image` (string): URL of the first available product item image, or null if no image exists
- `cost` (number): Cost price
- `cost_currency` (string): Cost currency code (e.g., "EUR")
- `rrp` (number): Recommended retail price
- `rrp_currency` (string): RRP currency code (e.g., "EUR")
- `product_item_name` (string): Product item name in the specified language (falls back to product_item_code if no translation exists)

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "category_codes": ["The category_codes field is required."],
    "category_codes.0": ["The selected category_codes.0 is invalid."],
    "lang": ["The selected lang is invalid."]
  }
}
```

**Notes:**
- Returns all product items that belong to ANY of the specified categories
- Product items are deduplicated (if a product item belongs to multiple specified categories, it appears only once)
- Images are searched in both `variants` and `product-items` directory structures for compatibility
- Supports multiple image formats: jpg, jpeg, png, gif, bmp, webp
- Product item names are translated based on the specified language
- Only returns active product items

---

## 14. Get Product Code by ISKU

**Endpoint:** `POST /products/get-product-code`
**Purpose:** Get the parent product code for a given product item ISKU

**Request Body:**
```json
{
  "isku": "WHPL-180-120-SKU"
}
```

**Parameters:**
- `isku` (string, required): International Stock Keeping Unit (must exist in product_item table)

**Examples:**
- Get product code for a specific ISKU

**Response (200):**
```json
{
  "success": true,
  "data": {
    "isku": "WHPL-180-120-SKU",
    "product_code": "WHPL"
  }
}
```

**Response Fields:**
- `isku` (string): The ISKU that was queried
- `product_code` (string): The parent product code that contains this product item

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "isku": ["The selected isku is invalid."]
  }
}
```

**Notes:**
- This endpoint provides a simple lookup to find which product a product item belongs to
- Useful for navigation and relationship mapping in frontend applications
- Returns the direct parent product code for any valid ISKU

---

## 15. Get Entities by Tag

**Endpoint:** `GET /entities/by-tag/{tagCode}/{lang}`
**Purpose:** Get all products and product items that are associated with a specific tag code

**Path Parameters:**
- `tagCode` (string, required): Tag code to search for (can be either a product tag or product item tag)
- `lang` (string, required): Language code for titles (must exist in lkp_language table, 2 characters)

**Examples:**
- `GET /entities/by-tag/bestseller/en` - Get all entities tagged with "bestseller" in English
- `GET /entities/by-tag/eco_friendly/lt` - Get all entities tagged with "eco_friendly" in Lithuanian

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "type": "product",
      "code": "WHPL",
      "image_url": "http://127.0.0.1:8000/storage/products/WHPL/main/image.jpg",
      "cost": null,
      "cost_currency": null,
      "rrp": null,
      "rrp_currency": null,
      "title": "Workstation Footboard"
    },
    {
      "type": "product_item",
      "code": "WHPL-180-120-SKU",
      "image_url": "http://127.0.0.1:8000/storage/products/WHPL/product-items/WHPL-180-120/image.jpg",
      "cost": 840.50,
      "cost_currency": "EUR",
      "rrp": 950.00,
      "rrp_currency": "EUR",
      "title": "Heating Mat 180x120cm"
    }
  ]
}
```

**Response Fields:**
- `type` (string): Either "product" or "product_item"
- `code` (string): Product code for products, ISKU for product items
- `image_url` (string): URL of the first available image, or null if no image exists
- `cost` (number): Cost price (null for products, actual value for product items)
- `cost_currency` (string): Cost currency code (null for products, "EUR" for product items)
- `rrp` (number): Recommended retail price (null for products, actual value for product items)
- `rrp_currency` (string): RRP currency code (null for products, "EUR" for product items)
- `title` (string): Entity title in the specified language

**Response (200) - Empty Results:**
```json
{
  "success": true,
  "data": []
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Invalid language code"
}
```

**Notes:**
- Searches both product tags (`lkp_tag`) and product item tags (`lkp_item_tag`) tables
- Returns all products linked to the tag via `product_tag` table
- Returns all product items linked to the tag via `product_item_tag` table
- Images are automatically found in the appropriate directory structure
- Titles are translated based on the specified language
- Products don't have individual cost/RRP (returns null), only product items do
- Returns empty array if tag exists but no entities are associated with it
- Returns empty array if tag doesn't exist in either table

---

## API Features

- **Multi-language Support:** Products and attributes support multiple languages
- **Image Management:** Automatic folder creation and image filtering
- **Validation:** Comprehensive input validation with detailed error messages
- **Relationships:** Proper foreign key relationships and data integrity
- **Transactions:** Database transactions ensure data consistency
- **Excel Processing:** Automated Excel import with dimension conversions and relationship mapping
- **RESTful Design:** Standard REST API patterns and HTTP status codes
