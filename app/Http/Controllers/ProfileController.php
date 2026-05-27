<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => current_user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        current_user()->fill($request->validated());

        if (current_user()->isDirty('email')) {
            current_user()->email_verified_at = null;
        }

        current_user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's preferred locale.
     */
    public function updateLocale(Request $request): RedirectResponse
    {
        $request->validate([
            'preferred_locale' => ['required', 'string', 'in:' . implode(',', SetLocale::SUPPORTED)],
        ]);

        $locale = $request->input('preferred_locale');

        current_user()->update(['preferred_locale' => $locale]);

        // Sync session so the change takes effect immediately
        session(['locale' => $locale]);

        return Redirect::route('profile.edit')->with('success', 'locale-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = current_user();

        Auth::guard(current_guard())->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
