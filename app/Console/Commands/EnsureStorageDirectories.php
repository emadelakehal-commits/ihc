<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EnsureStorageDirectories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:ensure-directories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure all required storage directories exist';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Ensuring storage directories exist...');

        $directories = [
            'categories',
            'excel',
            'images',
            'product-documents',
            'products',
        ];

        foreach ($directories as $directory) {
            $path = storage_path('app/public/' . $directory);
            
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
                $this->line("Created directory: {$path}");
            } else {
                $this->line("Directory exists: {$path}");
            }
        }

        $this->info('All required storage directories are ready.');
    }
}