<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class DailyDigest extends Notification
{
     /**
     * Create a new notification instance.
     */
    public function __construct(public array $data)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $d = $this->data;

        $message = new SlackMessage;

        // ── Header ──────────────────────────────────────────
        $message->headerBlock("📊 Daily Digest — {$d['date']}");

        // ── Key Metrics ─────────────────────────────────────
        $message->sectionBlock(function (SectionBlock $b) use ($d) {
            $b->field("*📦 Orders: * {$d['order_count']}")->markdown();
            $b->field("*💰 Revenue: * " . format_price($d['revenue']))->markdown();
            $b->field("*👤 New Customers: * " . count($d['new_customers']))->markdown();
            $b->field("*💼 Failed Jobs: * " . ($d['failed_jobs_count'] ? "⚠️ {$d['failed_jobs_count']}" : '✅ None'))->markdown();
        });

        // ── New Customer Names (if any) ──────────────────────
        if (! empty($d['new_customers'])) {
            $message->dividerBlock();
            $message->sectionBlock(function (SectionBlock $b) use ($d) {
                $lines = implode(', ', $d['new_customers']);
                $b->text("*🆕 New Customers:*\n{$lines}")->markdown();
            });
        }

        $message->dividerBlock();

        // ── Low Stock ────────────────────────────────────────
        $message->sectionBlock(function (SectionBlock $b) use ($d) {
            $lowStock = $d['low_stock']; // plain array now

            if (empty($lowStock)) {
                $b->text('✅ *Stock:* All products adequately stocked')->markdown();
                return;
            }

            $lines = implode("\n", array_map(
                fn ($p) => "• *{$p['name']}* — {$p['stock']} left",  // array access, not ->
                $lowStock
            ));

            $b->text("⚠️ *Low Stock (" . count($lowStock) . " products)*\n{$lines}")->markdown();
        });

        // ── Footer ───────────────────────────────────────────
        $message->contextBlock(function (ContextBlock $b) {
            $b->text('🕘 Generated at ' . now()->format('d M Y, H:i') . ' · Laravel Scheduler');
        });

        return $message;
    }
}