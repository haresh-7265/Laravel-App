<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTicketRequest;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
     */
    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $ticket = $this->ticketService->create($request->validated());

        return redirect()->back()->with('success', 'Ticket submitted successfully. We will be in touch soon.');
    }
}