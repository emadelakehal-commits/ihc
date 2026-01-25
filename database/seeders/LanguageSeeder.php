<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['code' => 'af', 'name' => 'Afrikaans'],
            ['code' => 'am', 'name' => 'Amharic'],
            ['code' => 'ar', 'name' => 'Arabic'],
            ['code' => 'as', 'name' => 'Assamese'],
            ['code' => 'az', 'name' => 'Azerbaijani'],
            ['code' => 'bg', 'name' => 'Bulgarian'],
            ['code' => 'bn', 'name' => 'Bengali'],
            ['code' => 'bo', 'name' => 'Tibetan'],
            ['code' => 'bs', 'name' => 'Bosnian'],
            ['code' => 'ca', 'name' => 'Catalan'],
            ['code' => 'cs', 'name' => 'Czech'],
            ['code' => 'cy', 'name' => 'Welsh'],
            ['code' => 'da', 'name' => 'Danish'],
            ['code' => 'de', 'name' => 'German'],
            ['code' => 'el', 'name' => 'Greek'],
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'eo', 'name' => 'Esperanto'],
            ['code' => 'es', 'name' => 'Spanish'],
            ['code' => 'et', 'name' => 'Estonian'],
            ['code' => 'eu', 'name' => 'Basque'],
            ['code' => 'fa', 'name' => 'Persian'],
            ['code' => 'fi', 'name' => 'Finnish'],
            ['code' => 'fj', 'name' => 'Fijian'],
            ['code' => 'fr', 'name' => 'French'],
            ['code' => 'fy', 'name' => 'Frisian'],
            ['code' => 'ga', 'name' => 'Irish'],
            ['code' => 'gu', 'name' => 'Gujarati'],
            ['code' => 'haw', 'name' => 'Hawaiian'],
            ['code' => 'he', 'name' => 'Hebrew'],
            ['code' => 'hi', 'name' => 'Hindi'],
            ['code' => 'hr', 'name' => 'Croatian'],
            ['code' => 'hu', 'name' => 'Hungarian'],
            ['code' => 'hy', 'name' => 'Armenian'],
            ['code' => 'id', 'name' => 'Indonesian'],
            ['code' => 'is', 'name' => 'Icelandic'],
            ['code' => 'it', 'name' => 'Italian'],
            ['code' => 'ja', 'name' => 'Japanese'],
            ['code' => 'ka', 'name' => 'Georgian'],
            ['code' => 'kk', 'name' => 'Kazakh'],
            ['code' => 'km', 'name' => 'Khmer'],
            ['code' => 'kn', 'name' => 'Kannada'],
            ['code' => 'ko', 'name' => 'Korean'],
            ['code' => 'ky', 'name' => 'Kyrgyz'],
            ['code' => 'la', 'name' => 'Latin'],
            ['code' => 'lb', 'name' => 'Luxembourgish'],
            ['code' => 'lo', 'name' => 'Lao'],
            ['code' => 'lt', 'name' => 'Lithuanian'],
            ['code' => 'lv', 'name' => 'Latvian'],
            ['code' => 'mg', 'name' => 'Malagasy'],
            ['code' => 'mi', 'name' => 'Maori'],
            ['code' => 'mk', 'name' => 'Macedonian'],
            ['code' => 'ml', 'name' => 'Malayalam'],
            ['code' => 'mn', 'name' => 'Mongolian'],
            ['code' => 'mr', 'name' => 'Marathi'],
            ['code' => 'ms', 'name' => 'Malay'],
            ['code' => 'mt', 'name' => 'Maltese'],
            ['code' => 'my', 'name' => 'Burmese'],
            ['code' => 'ne', 'name' => 'Nepali'],
            ['code' => 'nl', 'name' => 'Dutch'],
            ['code' => 'no', 'name' => 'Norwegian'],
            ['code' => 'or', 'name' => 'Oriya'],
            ['code' => 'pa', 'name' => 'Punjabi'],
            ['code' => 'pl', 'name' => 'Polish'],
            ['code' => 'pt', 'name' => 'Portuguese'],
            ['code' => 'ro', 'name' => 'Romanian'],
            ['code' => 'ru', 'name' => 'Russian'],
            ['code' => 'sa', 'name' => 'Sanskrit'],
            ['code' => 'si', 'name' => 'Sinhala'],
            ['code' => 'sk', 'name' => 'Slovak'],
            ['code' => 'sl', 'name' => 'Slovenian'],
            ['code' => 'sm', 'name' => 'Samoan'],
            ['code' => 'sq', 'name' => 'Albanian'],
            ['code' => 'sr', 'name' => 'Serbian'],
            ['code' => 'sv', 'name' => 'Swedish'],
            ['code' => 'sw', 'name' => 'Swahili'],
            ['code' => 'ta', 'name' => 'Tamil'],
            ['code' => 'te', 'name' => 'Telugu'],
            ['code' => 'tg', 'name' => 'Tajik'],
            ['code' => 'th', 'name' => 'Thai'],
            ['code' => 'tl', 'name' => 'Tagalog'],
            ['code' => 'to', 'name' => 'Tongan'],
            ['code' => 'tr', 'name' => 'Turkish'],
            ['code' => 'ug', 'name' => 'Uyghur'],
            ['code' => 'ur', 'name' => 'Urdu'],
            ['code' => 'uz', 'name' => 'Uzbek'],
            ['code' => 'vi', 'name' => 'Vietnamese'],
            ['code' => 'zh', 'name' => 'Chinese'],
        ];

        foreach ($languages as $language) {
            \App\Models\Language::updateOrCreate(
                ['code' => $language['code']],
                ['name' => $language['name']]
            );
        }
    }
}
