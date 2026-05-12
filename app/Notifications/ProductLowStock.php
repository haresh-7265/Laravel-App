<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\{SectionBlock};
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductLowStock extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly Product $product)
    {
        //
    }

    /**
     * Delivery channels: email + database + slack.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'slack'];
    }

    /**
     * Route mail and slack to the "emails" queue.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
            'slack' => 'emails',
        ];
    }

    /**
     * Admin email — reuses the existing low-stock markdown template.
     * Locale-aware subject via __().
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ '.__('Low Stock Alert').': '.$this->product->name)
            ->markdown('emails.admin.low-stock', [
                'product' => $this->product,
            ]);
    }

    /**
     * Data stored in the notifications table.
     * Uses __() for locale-aware database messages.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'stock' => $this->product->stock,
            'message' => __('Low stock alert: :product has only :stock units left', [
                'product' => $this->product->name,
                'stock' => $this->product->stock,
            ]),
            'icon' => 'alert',
        ];
    }

    /**
     * Post a low-stock alert to the #alerts Slack channel.
     *
     * - Bulleted section listing the product details
     * - Mentions @warehouse user group when stock is critical (< 5 units)
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $isCritical = $this->hasCriticalStock();
        $stock      = $this->product->stock;
        $emoji      = $isCritical ? '🔴' : '🟡';
        $flag       = $isCritical ? ' *— CRITICAL*' : '';

        $message = (new SlackMessage)
            ->to(config('services.slack.notifications.alerts_channel', '#alerts'))
            ->text(":warning: Low-stock alert — {$this->product->name} needs attention.")
            ->headerBlock(':warning: Low-Stock Alert');

        // Warehouse mention for critical stock levels
        if ($isCritical) {
            $message->sectionBlock(function (SectionBlock $block) {
                // <!subteam^ID> is Slack mrkdwn syntax to ping a user group (@warehouse).
                $groupId = config('services.slack.warehouse_group_id', 'SXXXXXXXXXX');
                $block->text("<@{$groupId}> *Critical stock levels detected!* Immediate action required.")
                      ->markdown();
            });
        }

        // Bulleted product details section
        $message->dividerBlock()
            ->sectionBlock(function (SectionBlock $block) use ($stock, $emoji, $flag) {
                $lines = implode("\n", [
                    "{$emoji} *Product:* {$this->product->name}{$flag}",
                    "• *SKU:* `{$this->product->slug}`",
                    "• *Stock remaining:* {$stock} unit(s)",
                    "• *Price:* \${$this->product->price}",
                ]);

                $block->text($lines)->markdown();
            });

        return $message;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Determine if the product's stock level is critical (< 5 units).
     */
    private function hasCriticalStock(): bool
    {
        return $this->product->stock < 5;
    }

    /**
     * Handle notification failure — log the error.
     */
    public function failed(Throwable $e): void
    {
        Log::channel('product')->error('ProductLowStock notification failed', [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
