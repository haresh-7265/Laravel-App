<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\FailedJobsSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendFailedJobsSummary extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jobs:failed-summary {--hours=24 : Hours to look back}';

    /**
     * The console command description.
     */
    protected $description = 'Email admin a summary of failed jobs from the last 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $since = Carbon::now()->subHours($hours);

        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', $since)
            ->orderByDesc('failed_at')
            ->get(['uuid', 'queue', 'payload', 'exception', 'failed_at']);

        if ($failedJobs->isEmpty()) {
            $this->info("No failed jobs in the last {$hours} hours. No email sent.");

            return self::SUCCESS;
        }

        // Parse job names from payload
        $summary = $failedJobs->map(function ($job) {
            $payload = json_decode($job->payload, true);
            $displayName = class_basename($payload['displayName'] ?? $payload['job'] ?? 'Unknown');

            return [
                'uuid'      => $job->uuid,
                'job_name'  => $displayName,
                'queue'     => $job->queue,
                'error'     => str($job->exception)->limit(200),
                'failed_at' => Carbon::parse($job->failed_at)->toDateTimeString(),
            ];
        });

        // Group by job name for the overview
        $grouped = $summary->groupBy('job_name')->map->count();

        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found to notify.');

            return self::FAILURE;
        }

        Notification::send($admins, new FailedJobsSummary(
            failedJobs: $summary->toArray(),
            groupedCounts: $grouped->toArray(),
            hours: $hours,
        ));

        $this->info("Failed jobs summary sent to {$admins->count()} admin(s). Total failures: {$failedJobs->count()}.");

        return self::SUCCESS;
    }
}
