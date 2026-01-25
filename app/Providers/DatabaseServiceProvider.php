<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only run in local/development environment and when not in console
        if (app()->environment(['local', 'development']) && !$this->app->runningInConsole()) {
            Log::info("DatabaseServiceProvider: Starting database check");
            $this->ensureDatabaseExists();
        } else {
            Log::info("DatabaseServiceProvider: Skipping - environment: " . app()->environment() . ", console: " . $this->app->runningInConsole());
        }
    }

    /**
     * Ensure the database exists, create it if it doesn't
     */
    private function ensureDatabaseExists()
    {
        $database = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        try {
            // Try to connect to the database
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            // Database doesn't exist or connection failed
            Log::info("Database '{$database}' not found, attempting to create it...");

            try {
                // Connect without database to create it
                $pdo = new \PDO("mysql:host=$host", $username, $password);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                Log::info("Database '{$database}' created successfully");

                // Now try to connect again
                DB::reconnect();

                // Check if tables exist, if not, run migrations
                $tables = DB::select('SHOW TABLES');
                if (empty($tables)) {
                    Log::info("No tables found, running migrations...");
                    $this->runMigrations();
                }

            } catch (\Exception $createException) {
                Log::error("Failed to create database '{$database}': " . $createException->getMessage());
                // Don't throw exception to avoid breaking the app
            }
        }
    }

    /**
     * Run migrations if database is empty
     */
    private function runMigrations()
    {
        try {
            // Run migrations
            \Artisan::call('migrate', ['--force' => true]);
            Log::info("Migrations completed successfully");

            // Run essential seeders
            \Artisan::call('db:seed', [
                '--class' => 'LanguageSeeder',
                '--force' => true
            ]);
            \Artisan::call('db:seed', [
                '--class' => 'CurrencySeeder',
                '--force' => true
            ]);

            Log::info("Essential seeders completed");

        } catch (\Exception $e) {
            Log::error("Failed to run migrations: " . $e->getMessage());
            // Try alternative approach - run the setup command
            try {
                \Artisan::call('ihc:setup-fresh-database', ['--force' => true]);
                Log::info("Fallback setup completed using ihc:setup-fresh-database");
            } catch (\Exception $fallbackException) {
                Log::error("Fallback setup also failed: " . $fallbackException->getMessage());
            }
        }
    }
}
