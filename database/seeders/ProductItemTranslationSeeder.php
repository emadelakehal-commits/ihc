<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductItemTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder is designed to be run after product items are created
        // It provides translations for existing product items
        // Example structure for future use:

        /*
        $translations = [
            // English translations
            [
                'isku' => 'WHPL-180-120-SKU',
                'language' => 'en',
                'title' => 'Heating Mat 180x120cm',
                'short_desc' => 'Professional heating mat for industrial use'
            ],
            [
                'isku' => 'WHPL-180-120-SKU',
                'language' => 'lt',
                'title' => 'Šildymo danga 180x120cm',
                'short_desc' => 'Profesionali šildymo danga pramoniniam naudojimui'
            ],
        ];

        foreach ($translations as $translation) {
            \App\Models\ProductItemTranslation::updateOrCreate(
                [
                    'isku' => $translation['isku'],
                    'language' => $translation['language']
                ],
                [
                    'title' => $translation['title'],
                    'short_desc' => $translation['short_desc']
                ]
            );
        }
        */
    }
}
