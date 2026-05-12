<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\SlackInteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlackInteractionController extends Controller
{
    public function __construct(private readonly SlackInteractionService $slack) {}

    /**
     * POST /api/slack/interactions
     * Receives button click payloads from Slack, updates ticket, replies in thread.
     */
    public function handle(Request $request): JsonResponse
    {
        // Decode + validate payload type
        $payload = $this->slack->decodePayload($request->input('payload', ''));

        if (! $payload) {
            return response()->json(['error' => 'Unsupported payload type.'], 400);
        }

        // Extract action data
        $action = $this->slack->extractAction($payload);

        if (! $action) {
            return response()->json(['error' => 'Unknown action.'], 400);
        }

        // Find ticket
        $ticket = SupportTicket::find($action['ticket_id']);

        if (! $ticket) {
            return response()->json(['error' => 'Ticket not found.'], 404);
        }

        // Process action → update DB → get confirmation text
        $responseText = $this->slack->processAction(
            actionType: $action['type'],
            ticket:     $ticket,
            slackUser:  $action['slack_user'],
        );

        // Post thread reply
        $this->slack->postThreadReply(
            channel:  $action['channel'] ?? $ticket->slack_channel_id,
            threadTs: $action['thread_ts'] ?? $ticket->slack_message_ts,
            text:     $responseText,
        );

        // Slack requires 200 within 3 seconds
        return response()->json(['ok' => true]);
    }
}