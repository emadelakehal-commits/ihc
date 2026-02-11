<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register custom console commands
Artisan::command('ihc:setup-database', function () {
    $this->call(\App\Console\Commands\SetupIhcDatabase::class);
})->purpose('Set up the IHC database with migrations and seeders');

Artisan::command('ihc:setup-fresh-database', function () {
    $this->call(\App\Console\Commands\SetupFreshDatabase::class);
})->purpose('Set up a fresh IHC database with migrations and seeders');

Artisan::command('storage:ensure-directories', function () {
    $this->call(\App\Console\Commands\EnsureStorageDirectories::class);
})->purpose('Ensure all required storage directories exist');

Artisan::command('images:optimize', function () {
    $this->call(\App\Console\Commands\OptimizeImages::class);
})->purpose('Optimize all product and category images for faster loading');
