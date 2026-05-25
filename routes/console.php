<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('report:admin')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('report:admin  --type=inventory')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('slack:daily-digest')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// Weekly full reindex — recovers from any drift between DB and Meilisearch
Schedule::command('products:reindex --fresh')
         ->weekly()
         ->sundays()
         ->at('02:00')
         ->runInBackground()
         ->withoutOverlapping();

// Push settings after every deploy (add to deploy script instead if preferred)
Schedule::command('scout:sync-index-settings')
         ->daily()
         ->at('01:00');