<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReindexProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:reindex {--fresh : flush before import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex products into Meilisearch. Use --fresh to flush first.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Flushing index...');
            $this->call('scout:flush', ['model' => 'App\Models\Product']);
        }

        $this->info('Syncing index settings...');
        $this->call('scout:sync-index-settings');

        $this->info('Importing products...');
        $this->call('scout:import', ['model' => 'App\Models\Product']);

        $this->info('Done.');

        return self::SUCCESS;
    }
}
