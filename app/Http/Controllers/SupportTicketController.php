<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTicketRequest;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(private readonly SupportTicketService $ticketService) {}

     /**
     * GET /support/tickets/create
     * Show the support form.
     */
    public function create(): View
    {
        return view('support.create');
    }

    /**
     * POST /support/tickets
     * Create a new support ticket and notify #support in Slack.
     * Rate limited: 3 tickets per day per user (or per IP for guests).
     */
    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        // ── Programmatic rate limiting: 3 tickets per day ───────────────
        $key = 'support-tickets:' . (current_user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $hours = ceil($seconds / 3600);

            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'subject' => "You've reached the daily ticket limit (3 per day). Please try again in {$hours} " . \Str::plural('hour', $hours) . ".",
                ]);
        }

        RateLimiter::hit($key, 86400); // decay in 24 hours

        $ticket = $this->ticketService->create($request->validated());

        return redirect()->back()->with('success', 'Ticket submitted successfully. We will be in touch soon.');
    }
}