<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle an admin login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            \Log::channel('security')->info('Admin logged in', [
                'admin_id'   => Auth::guard('admin')->id(),
                'email'      => Auth::guard('admin')->user()->email,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended(route('products.index'))->with('success', __('Welcome back, :name!', ['name' => current_user()->name]));
        }

        return back()->withErrors([
            'email' => __('auth.failed'),
        ])->onlyInput('email');
    }

    /**
     * Handle an admin logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        // Don't invalidate the entire session — only regenerate the token
        // so the customer's web guard session remains intact.
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
