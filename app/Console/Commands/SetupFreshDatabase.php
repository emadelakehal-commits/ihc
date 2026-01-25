<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupFreshDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ihc:setup-fresh-database {--seed : Run database seeders after setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up a fresh IHC database with the current baseline schema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up fresh IHC database...');

        // Create database if not exists (before any other operations)
        $this->createDatabaseIfNotExists();

        if (!$this->confirm('This will drop all existing tables and recreate them. Continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop all existing tables
        $this->dropAllTables();

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('All existing tables dropped.');

        // Run all migrations
        $this->call('migrate', ['--force' => true]);

        $this->info('All migrations completed successfully.');

        // Run seeders if requested
        if ($this->option('seed')) {
            $this->info('Running database seeders...');
            $this->call('db:seed', ['--force' => true]);
            $this->info('Database seeding completed.');
        }

        $this->info('Fresh IHC database setup completed!');
        $this->info('You can now start fresh migrations from this baseline.');
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

    /**
     * Drop all existing tables
     */
    private function dropAllTables()
    {
        $tables = [
            'product_related',
            'product_item_tag',
            'lkp_item_tag_translation',
            'lkp_item_tag',
            'product_tag',
            'lkp_tag_translation',
            'lkp_tag',
            'product_category',
            'product_document',
            'product_delivery',
            'product_attribute_value',
            'lkp_attribute',
            'product_item_translation',
            'product_item',
            'product_translation',
            'product',
            'lkp_category_translation',
            'lkp_category',
            'lkp_currency',
            'domain',
            'lkp_language',
            'job_batches',
            'jobs',
            'cache_locks',
            'cache',
            'failed_jobs',
            'password_reset_tokens',
            'users',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
            $this->line("Dropped table: {$table}");
        }
    }
}
