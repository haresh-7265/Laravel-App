<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Http\Requests\ProfileUpdateRequest;
use App\Mail\EmailChangeAlertMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $this->authorize('view', current_user());

        return view('profile.edit', [
            'user' => current_user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->authorize('update', current_user());

        $user = current_user();
        $user->fill($request->validated());

        if ($user->isDirty('email') && ($user instanceof User)) {
            $oldEmail = $user->getOriginal('email');

            $user->email_verified_at = null;
            $user->save();

            // Send new verification email immediately
            $user->sendEmailVerificationNotification();

            // Generate signed cancellation URL valid for 24 hours
            $cancelUrl = URL::temporarySignedRoute(
                'profile.cancel-email-change',
                now()->addHours(24),
                ['user' => $user->id, 'old_email' => $oldEmail]
            );

            // Send security alert notification to the old email address
            Mail::to($oldEmail)->send(new EmailChangeAlertMail($user, $oldEmail, $cancelUrl));
        } else {
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's preferred locale.
     */
    public function updateLocale(Request $request): RedirectResponse
    {
        $this->authorize('update', current_user());

        $request->validate([
            'preferred_locale' => ['required', 'string', 'in:'.implode(',', SetLocale::SUPPORTED)],
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
        $user = current_user();
        $this->authorize('delete', $user);

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        Auth::guard(current_guard())->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Cancel an in-progress email change and revert to the old email.
     */
    public function cancelEmailChange(Request $request, User $user): RedirectResponse
    {
        $oldEmail = $request->query('old_email');

        if (!$oldEmail) {
            abort(400, 'Invalid parameters.');
        }

        $user->forceFill([
            'email' => $oldEmail,
            'email_verified_at' => now(), // Revert and mark verified
        ])->save();

        if (Auth::check() && Auth::id() === $user->id) {
            return Redirect::route('profile.edit')->with('success', 'Email change cancelled. Your email has been reverted and verified.');
        }

        return Redirect::route('login')->with('success', 'Email change cancelled. Your email has been reverted and verified.');
    }
}
