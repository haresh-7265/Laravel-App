<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\DailyDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class SlackDailyDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:daily-digest {--preview : Post to #bot-testing instead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily digest to Slack';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $yesterday = Carbon::yesterday();

        $orderCount = Order::whereDate('created_at', $yesterday)->count();
        $revenue = Order::whereDate('created_at', $yesterday)->sum('total');
        $newCustomers = User::whereDate('created_at', $yesterday)->pluck('name')->toArray();
        $lowStockProducts = Product::where('stock', '<', 10)
            ->orderBy('stock')
            ->get(['name', 'stock'])
            ->map(fn ($p) => [
                'name' => $p->name,
                'stock' => $p->stock,
            ])
            ->values()
            ->toArray();
        $failedJobsCount = \DB::table('failed_jobs')
            ->whereDate('failed_at', $yesterday)
            ->count();

        $data = [
            'order_count' => $orderCount,
            'revenue' => $revenue,
            'new_customers' => $newCustomers,
            'low_stock' => $lowStockProducts,
            'failed_jobs_count' => $failedJobsCount,
            'date' => $yesterday->toIso8601String(),
        ];

        $this->info('Metrics collected:');
        $this->line("- Orders: {$orderCount}");
        $this->line("- Revenue: " . format_price($revenue));
        $this->line("- New Customers: " . count($newCustomers));
        $this->line("- Low Stock: " . count($lowStockProducts));
        $this->line("- Failed Jobs: {$failedJobsCount}");

        $preview = $this->option('preview');
        $webhookUrl = $preview ? config('services.slack.webhooks.bot-testing') : config('services.slack.webhooks.leadership');

        if (! $webhookUrl) {
            $this->error('WebhookUrl not configured');
        }

        $this->info('Sending notification to Slack...');
        // on-demand — no User model needed
        Notification::route('slack', $webhookUrl)
            ->notify(new DailyDigest($data));

        $this->info('Digest sent successfully');
    }
}
