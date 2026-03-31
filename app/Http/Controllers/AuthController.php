<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ─── Show Forms ───────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    // ─── Register ─────────────────────────────────────────────

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->symbols()->mixedCase()],
        ]);

        $user = User::create($validated);

        // Log the user in immediately after registration
        Auth::login($user);

        // Regenerate session id after login 
        $request->session()->regenerate();

        // Merge guest cart into user cart here
        app(CartService::class)->mergeSessionCart();

        \Log::channel('security')->info('New user registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Account created! Welcome, ' . $user->name);
    }

    // ─── Login ────────────────────────────────────────────────

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Second arg = remember me (persistent cookie)
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Regenerate session ID to prevent session fixation attacks
            $request->session()->regenerate();

            // Merge guest cart into user cart here
            app(CartService::class)->mergeSessionCart();

            \Log::channel('security')->info('User logged in', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'remember_me' => $request->boolean('remember'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Redirect to originally requested URL or dashboard
            return redirect()->intended(route('products.index'));
        }

        \Log::channel('security')->warning('Failed login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Failed — send back with a single generic error 
        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'These credentials do not match our records.']);
    }

    // ─── Logout ───────────────────────────────────────────────

    public function logout(Request $request)
    {
        $userId = Auth::id();
        $email = Auth::user()->email;

        Auth::logout();

        // Invalidate session & regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        \Log::channel('security')->info('User logged out', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect()->route('login')
            ->with('success', 'You have been logged out.');
    }
}