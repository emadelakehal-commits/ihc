<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            ['name' => 'cable', 'unit' => null],
            ['name' => 'colour', 'unit' => null],
            ['name' => 'length', 'unit' => 'mm'],
            ['name' => 'other', 'unit' => null],
            ['name' => 'plug', 'unit' => null],
            ['name' => 'power', 'unit' => 'W'],
            ['name' => 'thickness', 'unit' => 'mm'],
            ['name' => 'warranty', 'unit' => null],
            ['name' => 'weight', 'unit' => 'g'],
            ['name' => 'width', 'unit' => 'mm'],
            ['name' => 'diameter', 'unit' => 'mm'],
            ['name' => 'covered_area', 'unit' => 'm²'],
            ['name' => 'cold_lead', 'unit' => null],
            ['name' => 'cold_lead_length', 'unit' => 'm'],
            ['name' => 'outside_jacket_material', 'unit' => null],
            ['name' => 'inside_jacket_material', 'unit' => null],
            ['name' => 'certificates', 'unit' => null],
            ['name' => 'voltage', 'unit' => 'V'],
            ['name' => 'total_wattage', 'unit' => 'W'],
            ['name' => 'watt_m2', 'unit' => 'W/m²'],
            ['name' => 'amp', 'unit' => 'A'],
            ['name' => 'fire_retardent', 'unit' => null],
            ['name' => 'product_warranty', 'unit' => null],
            ['name' => 'self_adhesive', 'unit' => null],
            ['name' => 'includes', 'unit' => null],
            ['name' => 'ip_class', 'unit' => null],
            ['name' => 'ohm', 'unit' => 'Ω'],
        ];

        foreach ($attributes as $attribute) {
            \App\Models\Attribute::updateOrCreate(
                ['name' => $attribute['name']],
                ['unit' => $attribute['unit']]
            );
        }
    }
}
