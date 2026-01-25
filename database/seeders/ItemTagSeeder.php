<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $itemTags = [
            'in_stock',
            'out_of_stock',
            'discontinued',
            'backorder',
            'pre_order',
            'clearance',
            'refurbished',
            'demo_unit',
            'floor_model',
            'open_box',
            'damaged',
            'warranty_expired',
            'needs_repair',
            'vintage',
            'collectible',
        ];

        foreach ($itemTags as $itemTagCode) {
            \App\Models\ItemTag::updateOrCreate(
                ['item_tag_code' => $itemTagCode],
                ['item_tag_code' => $itemTagCode]
            );
        }
    }
}
