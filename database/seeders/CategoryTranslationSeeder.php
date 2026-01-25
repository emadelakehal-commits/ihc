<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // English translations
            ['category_code' => 'FLOOR_HEATING', 'language' => 'en', 'name' => 'Floor Heating'],
            ['category_code' => 'WALL_HEATING', 'language' => 'en', 'name' => 'Wall Heating'],
            ['category_code' => 'SPECIAL_APPLICATIONS', 'language' => 'en', 'name' => 'Special Applications'],
            ['category_code' => 'INSULATION', 'language' => 'en', 'name' => 'Insulation'],
            ['category_code' => 'THERMOSTAT', 'language' => 'en', 'name' => 'Thermostat'],
            ['category_code' => 'PERSONAL_HEATING', 'language' => 'en', 'name' => 'Personal Heating'],
            ['category_code' => 'ACCESSORIES', 'language' => 'en', 'name' => 'Accessories'],
            ['category_code' => 'TILES_STONE_SCREED', 'language' => 'en', 'name' => 'Tiles Stone Screed'],
            ['category_code' => 'LAMINATE_WOODEN_FLOATING', 'language' => 'en', 'name' => 'Laminate Wooden Floating'],
            ['category_code' => 'DRY_WALL', 'language' => 'en', 'name' => 'Dry Wall'],
            ['category_code' => 'DRUM_TANK', 'language' => 'en', 'name' => 'Drum Tank'],
            ['category_code' => 'SPA_ZONE_0', 'language' => 'en', 'name' => 'Spa Zone 0'],
            ['category_code' => 'OUTDOOR', 'language' => 'en', 'name' => 'Outdoor'],
            ['category_code' => 'BATHROOM', 'language' => 'en', 'name' => 'Bathroom'],
            ['category_code' => 'DUPLEX_FOAM', 'language' => 'en', 'name' => 'Duplex Foam'],
            ['category_code' => 'FOAM', 'language' => 'en', 'name' => 'Foam'],
            ['category_code' => 'WIFI_THERMOSTATS', 'language' => 'en', 'name' => 'WiFi Thermostats'],
            ['category_code' => 'STANDARD_THERMOSTATS', 'language' => 'en', 'name' => 'Standard Thermostats'],
            ['category_code' => 'TEMPERATURE_CONTROLLER', 'language' => 'en', 'name' => 'Temperature Controller'],
            ['category_code' => 'CARPET', 'language' => 'en', 'name' => 'Carpet'],
            ['category_code' => 'ECO_CARPET', 'language' => 'en', 'name' => 'Eco Carpet'],
            ['category_code' => 'RADIANT_PANEL', 'language' => 'en', 'name' => 'Radiant Panel'],
            ['category_code' => 'FOOTBOARD', 'language' => 'en', 'name' => 'Footboard'],
            ['category_code' => 'CHENILE_LINE', 'language' => 'en', 'name' => 'Chenile Line'],
            ['category_code' => 'DANEA_LINE', 'language' => 'en', 'name' => 'Danea Line'],
            ['category_code' => 'FASCHION', 'language' => 'en', 'name' => 'Faschion'],
            ['category_code' => 'FIREPROOF_LINE', 'language' => 'en', 'name' => 'Fireproof Line'],
            ['category_code' => 'MELANGE', 'language' => 'en', 'name' => 'Melange'],
            ['category_code' => 'WOOL_LINE', 'language' => 'en', 'name' => 'Wool Line'],

            // Lithuanian translations
            ['category_code' => 'FLOOR_HEATING', 'language' => 'lt', 'name' => 'Grindų šildymas'],
            ['category_code' => 'WALL_HEATING', 'language' => 'lt', 'name' => 'Sienų šildymas'],
            ['category_code' => 'SPECIAL_APPLICATIONS', 'language' => 'lt', 'name' => 'Specialios aplikacijos'],
            ['category_code' => 'INSULATION', 'language' => 'lt', 'name' => 'Izoliacija'],
            ['category_code' => 'THERMOSTAT', 'language' => 'lt', 'name' => 'Termostatas'],
            ['category_code' => 'PERSONAL_HEATING', 'language' => 'lt', 'name' => 'Asmeninis šildymas'],
            ['category_code' => 'ACCESSORIES', 'language' => 'lt', 'name' => 'Priedai'],
            ['category_code' => 'TILES_STONE_SCREED', 'language' => 'lt', 'name' => 'Plytelės akmens skiedinys'],
            ['category_code' => 'LAMINATE_WOODEN_FLOATING', 'language' => 'lt', 'name' => 'Laminatas medinis plūduriuojantis'],
            ['category_code' => 'DRY_WALL', 'language' => 'lt', 'name' => 'Sausa siena'],
            ['category_code' => 'DRUM_TANK', 'language' => 'lt', 'name' => 'Būgnas bakas'],
            ['category_code' => 'SPA_ZONE_0', 'language' => 'lt', 'name' => 'SPA zona 0'],
            ['category_code' => 'OUTDOOR', 'language' => 'lt', 'name' => 'Lauko'],
            ['category_code' => 'BATHROOM', 'language' => 'lt', 'name' => 'Vonios kambarys'],
            ['category_code' => 'DUPLEX_FOAM', 'language' => 'lt', 'name' => 'Dvipusė puta'],
            ['category_code' => 'FOAM', 'language' => 'lt', 'name' => 'Puta'],
            ['category_code' => 'WIFI_THERMOSTATS', 'language' => 'lt', 'name' => 'WiFi termostatai'],
            ['category_code' => 'STANDARD_THERMOSTATS', 'language' => 'lt', 'name' => 'Standartiniai termostatai'],
            ['category_code' => 'TEMPERATURE_CONTROLLER', 'language' => 'lt', 'name' => 'Temperatūros valdiklis'],
            ['category_code' => 'CARPET', 'language' => 'lt', 'name' => 'Kilimas'],
            ['category_code' => 'ECO_CARPET', 'language' => 'lt', 'name' => 'Eko kilimas'],
            ['category_code' => 'RADIANT_PANEL', 'language' => 'lt', 'name' => 'Spinduliuojantis skydas'],
            ['category_code' => 'FOOTBOARD', 'language' => 'lt', 'name' => 'Pėdų lenta'],
            ['category_code' => 'CHENILE_LINE', 'language' => 'lt', 'name' => 'Chenile Line'],
            ['category_code' => 'DANEA_LINE', 'language' => 'lt', 'name' => 'Danea Line'],
            ['category_code' => 'FASCHION', 'language' => 'lt', 'name' => 'Faschion'],
            ['category_code' => 'FIREPROOF_LINE', 'language' => 'lt', 'name' => 'Fireproof Line'],
            ['category_code' => 'MELANGE', 'language' => 'lt', 'name' => 'Melange'],
            ['category_code' => 'WOOL_LINE', 'language' => 'lt', 'name' => 'Wool Line'],
        ];

        foreach ($translations as $translation) {
            \Illuminate\Support\Facades\DB::table('lkp_category_translation')->updateOrInsert(
                ['category_code' => $translation['category_code'], 'language' => $translation['language']],
                ['name' => $translation['name']]
            );
        }
    }
}
