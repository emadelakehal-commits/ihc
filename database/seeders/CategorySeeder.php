<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure public/categories directory exists
        if (!Storage::disk('public')->exists('categories')) {
            Storage::disk('public')->makeDirectory('categories');
        }

        // Create all categories with proper hierarchy (ALL CAPS for case insensitivity)
        $categories = [
            // Main categories (Level 1)
            'FLOOR_HEATING',
            'WALL_HEATING',
            'SPECIAL_APPLICATIONS',
            'INSULATION',
            'THERMOSTAT',
            'PERSONAL_HEATING',
            'ACCESSORIES',

            // Floor Heating subcategories (Level 2)
            'TILES_STONE_SCREED',
            'LAMINATE_WOODEN_FLOATING',

            // Wall Heating subcategories (Level 2)
            'DRY_WALL',

            // Special Applications subcategories (Level 2)
            'DRUM_TANK',
            'SPA_ZONE_0',
            'OUTDOOR',
            'BATHROOM',

            // Insulation subcategories (Level 2)
            'DUPLEX_FOAM',
            'FOAM',

            // Thermostat subcategories (Level 2)
            'WIFI_THERMOSTATS',
            'STANDARD_THERMOSTATS',
            'TEMPERATURE_CONTROLLER',

            // Personal Heating subcategories (Level 2)
            'CARPET',
            'ECO_CARPET',
            'RADIANT_PANEL',
            'FOOTBOARD',

            // Carpet sub-subcategories (Level 3) - Separate categories
            'CHENILE_LINE',
            'DANEA_LINE',
            'FASCHION',
            'FIREPROOF_LINE',
            'MELANGE',
            'WOOL_LINE',
        ];

        foreach ($categories as $categoryCode) {
            \App\Models\Category::updateOrCreate(
                ['category_code' => $categoryCode],
                []
            );
        }

        // Create category hierarchy relationships
        $hierarchy = [
            // Floor Heating hierarchy
            ['category_code' => 'TILES_STONE_SCREED', 'parent_code' => 'FLOOR_HEATING'],
            ['category_code' => 'LAMINATE_WOODEN_FLOATING', 'parent_code' => 'FLOOR_HEATING'],

            // Wall Heating hierarchy
            ['category_code' => 'TILES_STONE_SCREED', 'parent_code' => 'WALL_HEATING'], // Note: same subcategory under different parents
            ['category_code' => 'DRY_WALL', 'parent_code' => 'WALL_HEATING'],

            // Special Applications hierarchy
            ['category_code' => 'DRUM_TANK', 'parent_code' => 'SPECIAL_APPLICATIONS'],
            ['category_code' => 'SPA_ZONE_0', 'parent_code' => 'SPECIAL_APPLICATIONS'],
            ['category_code' => 'OUTDOOR', 'parent_code' => 'SPECIAL_APPLICATIONS'],
            ['category_code' => 'BATHROOM', 'parent_code' => 'SPECIAL_APPLICATIONS'],

            // Insulation hierarchy
            ['category_code' => 'DUPLEX_FOAM', 'parent_code' => 'INSULATION'],
            ['category_code' => 'FOAM', 'parent_code' => 'INSULATION'],

            // Thermostat hierarchy
            ['category_code' => 'WIFI_THERMOSTATS', 'parent_code' => 'THERMOSTAT'],
            ['category_code' => 'STANDARD_THERMOSTATS', 'parent_code' => 'THERMOSTAT'],
            ['category_code' => 'TEMPERATURE_CONTROLLER', 'parent_code' => 'THERMOSTAT'],

            // Personal Heating hierarchy
            ['category_code' => 'CARPET', 'parent_code' => 'PERSONAL_HEATING'],
            ['category_code' => 'ECO_CARPET', 'parent_code' => 'PERSONAL_HEATING'],
            ['category_code' => 'RADIANT_PANEL', 'parent_code' => 'PERSONAL_HEATING'],
            ['category_code' => 'FOOTBOARD', 'parent_code' => 'PERSONAL_HEATING'],

            // Carpet sub-hierarchy (Level 3) - Separate categories
            ['category_code' => 'CHENILE_LINE', 'parent_code' => 'CARPET'],
            ['category_code' => 'DANEA_LINE', 'parent_code' => 'CARPET'],
            ['category_code' => 'FASCHION', 'parent_code' => 'CARPET'],
            ['category_code' => 'FIREPROOF_LINE', 'parent_code' => 'CARPET'],
            ['category_code' => 'MELANGE', 'parent_code' => 'CARPET'],
            ['category_code' => 'WOOL_LINE', 'parent_code' => 'CARPET'],
        ];

        foreach ($hierarchy as $relation) {
            \Illuminate\Support\Facades\DB::table('category_hierarchy')->updateOrInsert(
                [
                    'category_code' => $relation['category_code'],
                    'parent_code' => $relation['parent_code']
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
