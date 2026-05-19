<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class LogSlowQueryJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;      // Don't retry — stale data isn't worth it
    public int $timeout = 5;    // Kill if it hangs

    public function __construct(protected array $data) {}

    public function handle(): void
    {
        DB::connection('analytics')->table('slow_queries')->insert($this->data);
    }
}