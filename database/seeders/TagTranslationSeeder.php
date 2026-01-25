<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // English translations
            ['tag_code' => 'bestseller', 'language' => 'en', 'name' => 'Bestseller'],
            ['tag_code' => 'new_arrival', 'language' => 'en', 'name' => 'New Arrival'],
            ['tag_code' => 'on_sale', 'language' => 'en', 'name' => 'On Sale'],
            ['tag_code' => 'featured', 'language' => 'en', 'name' => 'Featured'],
            ['tag_code' => 'limited_edition', 'language' => 'en', 'name' => 'Limited Edition'],
            ['tag_code' => 'eco_friendly', 'language' => 'en', 'name' => 'Eco Friendly'],
            ['tag_code' => 'premium', 'language' => 'en', 'name' => 'Premium'],
            ['tag_code' => 'budget_friendly', 'language' => 'en', 'name' => 'Budget Friendly'],
            ['tag_code' => 'handmade', 'language' => 'en', 'name' => 'Handmade'],
            ['tag_code' => 'customizable', 'language' => 'en', 'name' => 'Customizable'],

            // Lithuanian translations
            ['tag_code' => 'bestseller', 'language' => 'lt', 'name' => 'Bestselleris'],
            ['tag_code' => 'new_arrival', 'language' => 'lt', 'name' => 'Nauja prekė'],
            ['tag_code' => 'on_sale', 'language' => 'lt', 'name' => 'Išpardavimas'],
            ['tag_code' => 'featured', 'language' => 'lt', 'name' => 'Rekomenduojama'],
            ['tag_code' => 'limited_edition', 'language' => 'lt', 'name' => 'Ribotas tiražas'],
            ['tag_code' => 'eco_friendly', 'language' => 'lt', 'name' => 'Ekologiškas'],
            ['tag_code' => 'premium', 'language' => 'lt', 'name' => 'Premium'],
            ['tag_code' => 'budget_friendly', 'language' => 'lt', 'name' => 'Biudžetinis'],
            ['tag_code' => 'handmade', 'language' => 'lt', 'name' => 'Rankų darbo'],
            ['tag_code' => 'customizable', 'language' => 'lt', 'name' => 'Tinkintas'],
        ];

        foreach ($translations as $translation) {
            \App\Models\TagTranslation::updateOrCreate(
                ['tag_code' => $translation['tag_code'], 'language' => $translation['language']],
                ['name' => $translation['name']]
            );
        }
    }
}
