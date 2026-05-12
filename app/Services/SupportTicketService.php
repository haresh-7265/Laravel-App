<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Notifications\NewSupportTicket;
use Illuminate\Support\Facades\Notification;

class SupportTicketService
{
    /**
     * Create ticket + dispatch Slack notification.
     */
    public function create(array $data): SupportTicket
    {
        $ticket = SupportTicket::create([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'subject' => $data['subject'],
            'body' => $data['body'] ?? null,
            'priority' => $data['priority'] ?? SupportTicket::PRIORITY_MEDIUM,
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        $this->notifySlack($ticket);

        return $ticket;
    }

    private function notifySlack(SupportTicket $ticket): void
    {
        Notification::route('slack', config('services.slack.webhooks.support'))
            ->notify(new NewSupportTicket($ticket));
    }
}
