<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Rules\NotUsedPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.force-password-reset');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', new NotUsedPassword],
        ]);

        $wasForced = (bool) ($request->user()->force_password_reset ?? null);

        if ($request->user() instanceof User) {
            $request->user()->update([
                'password' => Hash::make($validated['password']),
                'force_password_reset' => false,
            ]);
        } elseif ($request->user() instanceof Admin) {
            $request->user()->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        Auth::logoutOtherDevices($validated['password']);

        // If the reset was forced, send the user to the dashboard with a success message.
        // Otherwise, stay on the profile/password page as before.
        if ($wasForced) {
            return redirect()->route('products.index')
                ->with('success', 'Your password has been updated successfully.');
        }

        return back()->with('success', 'Your password has been updated successfully.');
    }
}
