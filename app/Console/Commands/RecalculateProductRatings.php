<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class RecalculateProductRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:recalculate-ratings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculates the average rating (avg_rating) for every product in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting product rating recalculation...');

        $start = microtime(true);
        $memStart = memory_get_usage();

        Product::chunkById(500, function ($products) {
            foreach ($products as $product) {
                // Fetch the calculated average rating
                $average = $product->averageRating();
                
                // Update the product's avg_rating column
                $product->update(['avg_rating' => $average]);
            }
            
            $this->info('Processed a batch of ' . $products->count() . ' products.');
        });

        $time = round(microtime(true) - $start, 4);
        $mem  = round((memory_get_peak_usage() - $memStart) / 1024 / 1024, 2);


        $this->info('All product ratings have been successfully recalculated!');

        $this->info("Time   : {$time}s");
        $this->info("Memory : {$mem}MB");
    }
}
