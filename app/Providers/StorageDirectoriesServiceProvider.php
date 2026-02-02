<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class StorageDirectoriesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Listen for migration events
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Database\Events\MigrationsStarted::class,
            function ($event) {
                $this->ensureStorageDirectoriesExist();
            }
        );

        // Listen for seeding events
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Database\Events\SeedingStarted::class,
            function ($event) {
                $this->ensureStorageDirectoriesExist();
            }
        );

        // Also ensure directories exist on application boot (for web requests)
        if (app()->isBooted()) {
            $this->ensureStorageDirectoriesExist();
        } else {
            $this->ensureStorageDirectoriesExist();
        }
    }

    /**
     * Ensure required storage directories exist
     */
    private function ensureStorageDirectoriesExist()
    {
        $directories = [
            'categories',
            'excel',
            'images',
            'product-documents',
            'products',
        ];

        foreach ($directories as $directory) {
            $path = 'public/' . $directory;
            
            if (!Storage::disk('local')->exists($path)) {
                Storage::disk('local')->makeDirectory($path, 0755, true);
                \Illuminate\Support\Facades\Log::info("Created storage directory: {$path}");
            }
        }
    }
}