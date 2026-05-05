<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Store the chosen locale in session and redirect back.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => 'required|string|in:' . implode(',', SetLocale::SUPPORTED),
        ]);

        session(['locale' => $request->input('locale')]);

        return redirect()->back();
    }
}
