<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * GET /contact
     * Show the contact form.
     */
    public function create(): View
    {
        return view('contact.create');
    }

    /**
     * POST /contact
     * Send a contact message.
     * Rate limited: 5 per hour per IP to prevent spam.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // ── Programmatic rate limiting: 5 messages per hour per IP ──────
        $key = 'contact-form:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return back()
                ->withInput()
                ->withErrors([
                    'message' => "Too many contact submissions. Please try again in {$minutes} " . \Str::plural('minute', $minutes) . " ({$seconds}s).",
                ]);
        }

        RateLimiter::hit($key, 3600); // decay in 1 hour

        // Log the contact message
        Log::channel('single')->info('Contact form submission', [
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'subject' => $validated['subject'],
            'ip'      => $request->ip(),
        ]);

        // Send contact info to admin
        Mail::to(config('admin.email'))
            ->send(new ContactMessage($validated));

        return back()->with('success', 'Your message has been sent! We will get back to you soon.');
    }
}
