<?php

namespace Tests\Feature;

use App\Notifications\DailyDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SlackDailyDigestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.slack.webhooks.leadership',  'https://hooks.slack.com/fake/leadership');
        Config::set('services.slack.webhooks.bot-testing', 'https://hooks.slack.com/fake/bot-testing');
    }

    public function test_digest_sent_to_leadership(): void
    {
        Notification::fake();

        $this->artisan('slack:daily-digest')->assertSuccessful();

        Notification::assertSentOnDemand(
            DailyDigest::class,
            fn ($n, $channels, $notifiable) =>
                in_array('slack', $channels) &&
                $notifiable->routes['slack'] === 'https://hooks.slack.com/fake/leadership'
        );
    }

    public function test_preview_routes_to_bot_testing(): void
    {
        Notification::fake();

        $this->artisan('slack:daily-digest --preview')->assertSuccessful();

        Notification::assertSentOnDemand(
            DailyDigest::class,
            fn ($n, $channels, $notifiable) =>
                in_array('slack', $channels) &&
                $notifiable->routes['slack'] === 'https://hooks.slack.com/fake/bot-testing'
        );
    }

    public function test_digest_contains_required_fields(): void
    {
        Notification::fake();

        $this->artisan('slack:daily-digest --preview')->assertSuccessful();

        Notification::assertSentOnDemand(
            DailyDigest::class,
            fn ($n) => array_key_exists('order_count',       $n->data)
                    && array_key_exists('revenue',            $n->data)
                    && array_key_exists('new_customers',      $n->data)
                    && array_key_exists('low_stock',          $n->data)
                    && array_key_exists('failed_jobs_count',  $n->data)
                    && array_key_exists('date',               $n->data)
        );
    }
}