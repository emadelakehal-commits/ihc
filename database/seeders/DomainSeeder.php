<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = [
            ['code' => 'LT', 'domain' => 'lt.example.com', 'name' => 'Lithuania Domain'],
        ];

        foreach ($domains as $domainData) {
            \App\Models\Domain::updateOrCreate(
                ['code' => $domainData['code']],
                ['domain' => $domainData['domain'], 'name' => $domainData['name']]
            );
        }
    }
}
