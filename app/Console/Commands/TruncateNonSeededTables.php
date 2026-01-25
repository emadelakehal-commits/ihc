<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateNonSeededTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ihc:truncate-non-seeded {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all non-seeded tables in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Truncating non-seeded tables...');

        if (!$this->option('force') && !$this->confirm('This will remove all data from non-seeded tables. Continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Seeded tables (do not truncate)
        $seededTables = [
            'domain',
            'lkp_attribute',
            'lkp_category',
            'lkp_category_translation',
            'lkp_currency',
            'lkp_language',
            'lkp_tag',
            'lkp_tag_translation',
            'lkp_item_tag',
            'lkp_item_tag_translation',
            'category_hierarchy', // This is seeded by CategorySeeder
        ];

        // Get all tables
        $allTables = DB::select('SHOW TABLES');
        $database = config('database.connections.mysql.database');
        $tableKey = "Tables_in_{$database}";

        $nonSeededTables = [];
        foreach ($allTables as $table) {
            $tableName = $table->$tableKey;
            if (!in_array($tableName, $seededTables)) {
                $nonSeededTables[] = $tableName;
            }
        }

        if (empty($nonSeededTables)) {
            $this->info('No non-seeded tables found.');
            return;
        }

        $this->info('Non-seeded tables to truncate: ' . implode(', ', $nonSeededTables));

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables in order (reverse of creation or dependency order)
        $truncateOrder = [
            'product_related',
            'product_item_tag',
            'product_tag',
            'product_category',
            'product_document',
            'product_delivery',
            'product_attribute_value',
            'product_item_translation',
            'product_item',
            'product_translation',
            'product',
            'category_hierarchy',
            'job_batches',
            'jobs',
            'cache_locks',
            'cache',
            'failed_jobs',
            'password_reset_tokens',
            'sessions',
            'users',
            'migrations', // Though migrations table shouldn't be truncated in production
        ];

        foreach ($truncateOrder as $table) {
            if (in_array($table, $nonSeededTables)) {
                DB::statement("TRUNCATE TABLE `{$table}`");
                $this->line("Truncated table: {$table}");
            }
        }

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Non-seeded tables truncated successfully!');
    }
}
