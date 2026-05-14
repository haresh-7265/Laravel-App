<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ActionsBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class NewSupportTicket extends BaseNotification
{
    public function __construct(public readonly SupportTicket $ticket)
    {
        $this->onQueue('notifications');
    }

    /**
     * Delivery channel — Slack only.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function getPayload()
    {
        return $this->ticket->toArray();
    }

    /**
     * Build the interactive Slack message for the #support channel.
     *
     * Contains: ticket subject, customer name, priority badge,
     * and three action buttons for triage.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $ticket = $this->ticket;
        $emoji = $ticket->priorityEmoji();

        return (new SlackMessage)
            ->to(config('services.slack.notifications.support_channel', '#support'))
            ->text("🎫 New support ticket: {$ticket->subject}")
            ->headerBlock("🎫 New Support Ticket #{$ticket->id}")
            ->sectionBlock(function (SectionBlock $block) use ($ticket, $emoji) {
                $block->text(implode("\n", [
                    "*Subject:* {$ticket->subject}",
                    "*Customer:* {$ticket->customer_name} ({$ticket->customer_email})",
                    "*Priority:* {$emoji} {$ticket->priorityLabel()}",
                    "*Status:* {$ticket->statusLabel()}",
                ]))->markdown();
            })
            ->dividerBlock()
            ->actionsBlock(function (ActionsBlock $block) use ($ticket) {
                $block->button('Assign to me')
                    ->primary()
                    ->id("assign_to_me_{$ticket->id}")
                    ->value((string) $ticket->id);

                $block->button('Mark in progress')
                    ->id("mark_in_progress_{$ticket->id}")
                    ->value((string) $ticket->id);

                $block->button('Close')
                    ->danger()
                    ->id("close_ticket_{$ticket->id}")
                    ->value((string) $ticket->id);
            })
            ->contextBlock(function (ContextBlock $block) use ($ticket) {
                $block->text("Submitted at {$ticket->created_at->toDayDateTimeString()} | Ticket #{$ticket->id}");
            });
    }

}
