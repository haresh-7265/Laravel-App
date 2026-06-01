<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Store the chosen locale in session (and DB for authenticated users)
     * then redirect back.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => 'required|string|in:' . implode(',', SetLocale::SUPPORTED),
        ]);

        $locale = $request->input('locale');

        // Always write to session (works for guests & authenticated users)
        session(['locale' => $locale]);

        // For authenticated users, also persist to DB so it survives logout/login
        if (current_user()) {
            current_user()->update(['preferred_locale' => $locale]);
        }

        return redirect()->back()->with('success', __('Locale changed successfully'));
    }
}
