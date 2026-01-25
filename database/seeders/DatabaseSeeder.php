<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            CurrencySeeder::class,
            AttributeSeeder::class,
            CategorySeeder::class,
            CategoryTranslationSeeder::class,
            DomainSeeder::class,
            TagSeeder::class,
            TagTranslationSeeder::class,
            ItemTagSeeder::class,
            ItemTagTranslationSeeder::class,
        ]);
    }
}
