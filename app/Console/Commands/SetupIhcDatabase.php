<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupIhcDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ihc:setup-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup IHC database: create DB if not exists, run migrations, and seed lookup tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up IHC database...');

        // Create database if not exists
        $this->createDatabaseIfNotExists();

        // Run migrations (fresh to ensure clean state)
        $this->call('migrate:fresh', ['--force' => true]);

        // Run remaining migrations except the problematic one
        $this->call('migrate', ['--force' => true]);

        // Seed lookup tables
        $this->call('db:seed', ['--class' => 'LanguageSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'CurrencySeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'AttributeSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'CategorySeeder', '--force' => true]);

        $this->info('IHC database setup complete!');
    }

    private function createDatabaseIfNotExists()
    {
        $database = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        try {
            // Connect without database
            $pdo = new \PDO("mysql:host=$host", $username, $password);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("Database '$database' created or already exists.");
        } catch (\Exception $e) {
            $this->error('Failed to create database: ' . $e->getMessage());
        }
    }
}
