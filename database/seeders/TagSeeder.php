<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'bestseller',
            'new_arrival',
            'on_sale',
            'featured',
            'limited_edition',
            'eco_friendly',
            'premium',
            'budget_friendly',
            'handmade',
            'customizable',
        ];

        foreach ($tags as $tagCode) {
            \App\Models\Tag::updateOrCreate(
                ['tag_code' => $tagCode],
                ['tag_code' => $tagCode]
            );
        }
    }
}
