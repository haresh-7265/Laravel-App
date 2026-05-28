<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $email = $request->input('email');
        $throttleService = app(\App\Services\AuthThrottleService::class);

        if ($email) {
            // 1. Check if the account is currently locked out
            if ($seconds = $throttleService->checkLockout($email, 'admin')) {
                $throttleService->logAttempt($email, 'admin', false, $request->ip(), $request->userAgent(), 'Account locked out');
                
                return back()->withErrors([
                    'email' => __('auth.throttle', ['seconds' => $seconds]),
                ])->onlyInput('email');
            }

            // 2. CAPTCHA verification if required
            if ($throttleService->requiresCaptcha($email, 'admin')) {
                $request->validate([
                    'admin_captcha_answer' => ['required', 'string'],
                ]);

                $sessionAnswer = session('admin_captcha_answer');
                if (!$sessionAnswer || trim($request->input('admin_captcha_answer')) !== (string) $sessionAnswer) {
                    // Generate new question for subsequent attempt
                    $num1 = rand(1, 9);
                    $num2 = rand(1, 9);
                    session([
                        'admin_captcha_question' => "What is {$num1} + {$num2}?",
                        'admmin_captcha_answer' => $num1 + $num2,
                    ]);

                    $throttleService->logAttempt($email, 'admin', false, $request->ip(), $request->userAgent(), 'Incorrect CAPTCHA answer');
                    
                    return back()->withErrors([
                        'admin_captcha_answer' => 'Incorrect CAPTCHA answer.',
                    ])->onlyInput('email');
                }
            }
        }

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            // Reset throttle counters and clear CAPTCHA info
            if ($email) {
                $throttleService->resetFailedAttempts($email, 'admin');
            }
            session()->forget(['admin_captcha_question', 'admin_captcha_answer']);

            $request->session()->regenerate();

            $admin = Auth::guard('admin')->user();
            if (Hash::needsRehash($admin->password)) {
                $admin->update([
                    'password' => Hash::make($request->password),
                ]);
            }

            $throttleService->logAttempt($email, 'admin', true, $request->ip(), $request->userAgent());

            return redirect()->intended(route('products.index'))->with('success', __('Welcome back, :name!', ['name' => current_user()->name]));
        }

        // On authentication failure
        if ($email) {
            $throttleService->handleFailedAttempt($email, 'admin', $request->ip());
            $throttleService->logAttempt($email, 'admin', false, $request->ip(), $request->userAgent(), 'Invalid credentials');

            if ($throttleService->requiresCaptcha($email, 'admin')) {
                $num1 = rand(1, 9);
                $num2 = rand(1, 9);
                session([
                    'admin_captcha_question' => "What is {$num1} + {$num2}?",
                    'admin_captcha_answer' => $num1 + $num2,
                ]);
            }
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
