<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->checkAndSetupDatabase();
    }

    /**
     * Check if database setup is needed and perform auto-setup
     */
    private function checkAndSetupDatabase()
    {
        // Skip only for specific artisan commands that shouldn't trigger setup
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            $args = app('request')->server('argv') ?? [];
            $command = $args[1] ?? null;

            // Allow setup during 'serve' command (web server) but skip other problematic commands
            if (!in_array($command, ['serve']) &&
                in_array($command, ['migrate', 'db:seed', 'ihc:setup-database', 'config:cache', 'route:cache', 'make:migration', 'make:model'])) {
                return;
            }
        }

        try {
            // Check if we can connect to database
            DB::connection()->getPdo();

            // Check if essential tables exist
            $essentialTables = [
                'migrations',
                'lkp_language',
                'lkp_category',
                'lkp_category_translation',
                'product',
                'product_translation'
            ];

            $missingTables = [];
            foreach ($essentialTables as $table) {
                if (!Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            // Check if essential seed data exists
            $seedDataMissing = false;
            if (Schema::hasTable('lkp_language') && \App\Models\Language::count() == 0) {
                $seedDataMissing = true;
            }

            if (!empty($missingTables) || $seedDataMissing) {
                $this->performDatabaseSetup();
            }

        } catch (\Exception $e) {
            // Database doesn't exist or connection failed
            $this->performDatabaseSetup();
        }
    }

    /**
     * Perform automatic database setup
     */
    private function performDatabaseSetup()
    {
        try {
            // Run the IHC database setup command
            Artisan::call('ihc:setup-database', [], $this->getOutput());

            // Log the setup completion
            \Illuminate\Support\Facades\Log::info('IHC Database auto-setup completed successfully');

        } catch (\Exception $e) {
            // Log the error but don't crash the app
            \Illuminate\Support\Facades\Log::error('IHC Database auto-setup failed: ' . $e->getMessage());
        }
    }

    /**
     * Get output interface for artisan commands
     */
    private function getOutput()
    {
        if (app()->runningInConsole()) {
            return null; // Use default output
        }

        // For web requests, create a null output to suppress console output
        return new \Symfony\Component\Console\Output\NullOutput();
    }
}
