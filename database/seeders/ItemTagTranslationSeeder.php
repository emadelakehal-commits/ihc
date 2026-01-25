<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemTagTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // English translations
            ['item_tag_code' => 'in_stock', 'language' => 'en', 'name' => 'In Stock'],
            ['item_tag_code' => 'out_of_stock', 'language' => 'en', 'name' => 'Out of Stock'],
            ['item_tag_code' => 'discontinued', 'language' => 'en', 'name' => 'Discontinued'],
            ['item_tag_code' => 'backorder', 'language' => 'en', 'name' => 'Backorder'],
            ['item_tag_code' => 'pre_order', 'language' => 'en', 'name' => 'Pre-Order'],
            ['item_tag_code' => 'clearance', 'language' => 'en', 'name' => 'Clearance'],
            ['item_tag_code' => 'refurbished', 'language' => 'en', 'name' => 'Refurbished'],
            ['item_tag_code' => 'demo_unit', 'language' => 'en', 'name' => 'Demo Unit'],
            ['item_tag_code' => 'floor_model', 'language' => 'en', 'name' => 'Floor Model'],
            ['item_tag_code' => 'open_box', 'language' => 'en', 'name' => 'Open Box'],
            ['item_tag_code' => 'damaged', 'language' => 'en', 'name' => 'Damaged'],
            ['item_tag_code' => 'warranty_expired', 'language' => 'en', 'name' => 'Warranty Expired'],
            ['item_tag_code' => 'needs_repair', 'language' => 'en', 'name' => 'Needs Repair'],
            ['item_tag_code' => 'vintage', 'language' => 'en', 'name' => 'Vintage'],
            ['item_tag_code' => 'collectible', 'language' => 'en', 'name' => 'Collectible'],

            // Lithuanian translations
            ['item_tag_code' => 'in_stock', 'language' => 'lt', 'name' => 'Yra sandėlyje'],
            ['item_tag_code' => 'out_of_stock', 'language' => 'lt', 'name' => 'Nėra sandėlyje'],
            ['item_tag_code' => 'discontinued', 'language' => 'lt', 'name' => 'Nebegaminama'],
            ['item_tag_code' => 'backorder', 'language' => 'lt', 'name' => 'Užsakymas'],
            ['item_tag_code' => 'pre_order', 'language' => 'lt', 'name' => 'Išankstinis užsakymas'],
            ['item_tag_code' => 'clearance', 'language' => 'lt', 'name' => 'Išpardavimas'],
            ['item_tag_code' => 'refurbished', 'language' => 'lt', 'name' => 'Atnaujinta'],
            ['item_tag_code' => 'demo_unit', 'language' => 'lt', 'name' => 'Demonstracinis'],
            ['item_tag_code' => 'floor_model', 'language' => 'lt', 'name' => 'Parodomasis modelis'],
            ['item_tag_code' => 'open_box', 'language' => 'lt', 'name' => 'Atidaryta dėžė'],
            ['item_tag_code' => 'damaged', 'language' => 'lt', 'name' => 'Pažeista'],
            ['item_tag_code' => 'warranty_expired', 'language' => 'lt', 'name' => 'Garantija pasibaigusi'],
            ['item_tag_code' => 'needs_repair', 'language' => 'lt', 'name' => 'Reikia remonto'],
            ['item_tag_code' => 'vintage', 'language' => 'lt', 'name' => 'Vintažinis'],
            ['item_tag_code' => 'collectible', 'language' => 'lt', 'name' => 'Kolekcinis'],
        ];

        foreach ($translations as $translation) {
            \App\Models\ItemTagTranslation::updateOrCreate(
                ['item_tag_code' => $translation['item_tag_code'], 'language' => $translation['language']],
                ['name' => $translation['name']]
            );
        }
    }
}
