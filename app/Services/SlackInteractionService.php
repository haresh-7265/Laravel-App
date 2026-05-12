<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackInteractionService
{
    /**
     * Map action_id prefix → ticket status.
     */
    private const ACTION_STATUS_MAP = [
        'assign_to_me'     => SupportTicket::STATUS_IN_PROGRESS,
        'mark_in_progress' => SupportTicket::STATUS_IN_PROGRESS,
        'close_ticket'     => SupportTicket::STATUS_CLOSED,
    ];

    // -------------------------------------------------------------------------
    // Payload parsing
    // -------------------------------------------------------------------------

    public function decodePayload(string $raw): ?array
    {
        $payload = json_decode($raw, true);

        if (! $payload || ($payload['type'] ?? '') !== 'block_actions') {
            return null;
        }

        return $payload;
    }

    public function extractAction(array $payload): ?array
    {
        $action   = $payload['actions'][0] ?? null;
        $actionId = $action['action_id']   ?? '';

        $actionType = $this->resolveActionType($actionId);

        if (! $actionType) {
            return null;
        }

        return [
            'type'       => $actionType,
            'ticket_id'  => $action['value'] ?? null,
            'slack_user' => $payload['user'] ?? [],
            'channel'    => $payload['channel']['id'] ?? null,
            'thread_ts'  => $payload['message']['ts'] ?? null,
        ];
    }

    // -------------------------------------------------------------------------
    // Ticket action processing
    // -------------------------------------------------------------------------

    public function processAction(string $actionType, SupportTicket $ticket, array $slackUser): string
    {
        $slackUserName = $slackUser['username'] ?? $slackUser['name'] ?? 'Unknown';
        $slackUserId   = $slackUser['id'] ?? null;

        return match ($actionType) {
            'assign_to_me'     => $this->assignToMe($ticket, $slackUserId, $slackUserName),
            'mark_in_progress' => $this->markInProgress($ticket, $slackUserId),
            'close_ticket'     => $this->closeTicket($ticket, $slackUserId),
            default            => "⚠️ Unknown action on ticket #{$ticket->id}.",
        };
    }

    // -------------------------------------------------------------------------
    // Thread reply
    // -------------------------------------------------------------------------

    public function postThreadReply(string $channel, ?string $threadTs, string $text): void
    {
        $token = config('services.slack.bot_token');

        if (! $token || ! $channel) {
            Log::warning('Cannot post Slack thread reply: missing bot token or channel.');
            return;
        }

        try {
            $payload = ['channel' => $channel, 'text' => $text];

            if ($threadTs) {
                $payload['thread_ts'] = $threadTs;
            }

            $response = Http::withToken($token)
                ->post('https://slack.com/api/chat.postMessage', $payload);

            if (! $response->json('ok')) {
                Log::warning('Slack thread reply failed', [
                    'error'   => $response->json('error'),
                    'channel' => $channel,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Exception posting Slack thread reply', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Private action handlers
    // -------------------------------------------------------------------------

    private function assignToMe(SupportTicket $ticket, ?string $slackUserId, string $slackUserName): string
    {
        $adminUser = $slackUserName
            ? User::where('slack_username', $slackUserName)->first()
            : null;

        $ticket->update([
            'assigned_to' => $adminUser?->id,
            'status'      => SupportTicket::STATUS_IN_PROGRESS,
        ]);

        Log::info('Support ticket assigned', [
            'ticket_id'   => $ticket->id,
            'assigned_to' => $slackUserName,
            'admin_id'    => $adminUser?->id,
        ]);

        return "✅ <@{$slackUserId}> assigned *ticket #{$ticket->id}* to themselves and marked it *In Progress*.";
    }

    private function markInProgress(SupportTicket $ticket, ?string $slackUserId): string
    {
        $ticket->update(['status' => SupportTicket::STATUS_IN_PROGRESS]);

        Log::info('Support ticket marked in progress', ['ticket_id' => $ticket->id]);

        return "🔄 <@{$slackUserId}> marked *ticket #{$ticket->id}* as *In Progress*.";
    }

    private function closeTicket(SupportTicket $ticket, ?string $slackUserId): string
    {
        $ticket->update(['status' => SupportTicket::STATUS_CLOSED]);

        Log::info('Support ticket closed', ['ticket_id' => $ticket->id]);

        return "🔒 <@{$slackUserId}> closed *ticket #{$ticket->id}*.";
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveActionType(string $actionId): ?string
    {
        foreach (self::ACTION_STATUS_MAP as $prefix => $status) {
            if (str_starts_with($actionId, $prefix)) {
                return $prefix;
            }
        }

        return null;
    }
}