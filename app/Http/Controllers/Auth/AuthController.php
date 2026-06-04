<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Services\AuthThrottleService;
use App\Services\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle custom manual user login.
     */
    public function login(Request $request)
    {
        $email = $request->input('email');
        $throttleService = app(AuthThrottleService::class);

        if ($email) {
            // 1. Check if the account is currently locked out
            if ($seconds = $throttleService->checkLockout($email, 'web')) {
                $throttleService->logAttempt($email, 'web', false, $request->ip(), $request->userAgent(), 'Account locked out');

                throw ValidationException::withMessages([
                    'email' => __('auth.throttle', ['seconds' => $seconds]),
                ]);
            }

            // 2. CAPTCHA verification if required
            if ($throttleService->requiresCaptcha($email, 'web')) {
                $request->validate([
                    'captcha_answer' => ['required', 'string'],
                ]);

                $sessionAnswer = session('captcha_answer');
                if (! $sessionAnswer || trim($request->input('captcha_answer')) !== (string) $sessionAnswer) {
                    // Generate new question for subsequent attempt
                    $num1 = rand(1, 9);
                    $num2 = rand(1, 9);
                    session([
                        'captcha_question' => "What is {$num1} + {$num2}?",
                        'captcha_answer' => $num1 + $num2,
                    ]);

                    $throttleService->logAttempt($email, 'web', false, $request->ip(), $request->userAgent(), 'Incorrect CAPTCHA answer');

                    throw ValidationException::withMessages([
                        'captcha_answer' => 'Incorrect CAPTCHA answer.',
                    ]);
                }
            }
        }

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            // Reset throttle counters and clear CAPTCHA info
            if ($email) {
                $throttleService->resetFailedAttempts($email, 'web');
            }
            session()->forget(['captcha_question', 'captcha_answer']);

            $request->session()->regenerate();

            $cartService = app(CartService::class);
            $sessionCart = session($cartService->sessionKey, []);
            $cartService->mergeSessionCart($sessionCart);

            $user = Auth::guard('web')->user();

            if (Hash::needsRehash($user->password)) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
            }

            $throttleService->logAttempt($email, 'web', true, $request->ip(), $request->userAgent());

            return redirect()->intended(route('products.index'))->with('success', __('Welcome back, :name!', ['name' => $user->name]));
        }

        // On authentication failure
        if ($email) {
            $throttleService->handleFailedAttempt($email, 'web', $request->ip());
            $throttleService->logAttempt($email, 'web', false, $request->ip(), $request->userAgent(), 'Invalid credentials');

            if ($throttleService->requiresCaptcha($email, 'web')) {
                $num1 = rand(1, 9);
                $num2 = rand(1, 9);
                session([
                    'captcha_question' => "What is {$num1} + {$num2}?",
                    'captcha_answer' => $num1 + $num2,
                ]);
            }
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Handle custom manual user registration.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
        ]);

        $cartService = app(CartService::class);
        $sessionCart = session($cartService->sessionKey, []);
        Cache::tags(['users'])->flush();

        $user->assignRole('customer');

        event(new Registered($user));

        Auth::guard('web')->login($user);

        $cartService->mergeSessionCart($sessionCart);

        Log::channel('security')->info('New user registered (Manual)', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return redirect(route('products.index', absolute: false))->with('success', 'Registration successful!');
    }

    /**
     * Handle manual user logout.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Admin impersonation of a customer.
     */
    public function impersonate(Request $request, $id)
    {
        Gate::authorize('impersonate-users');

        $admin = Auth::guard('admin')->user();
        $user = User::findOrFail($id);

        Log::channel('security')->info('Admin started impersonating customer', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'customer_id' => $user->id,
            'customer_email' => $user->email,
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Login as the customer under web guard
        Auth::guard('web')->loginUsingId($id);

        session([
            'impersonate.active' => true,
            'impersonate.original_admin_id' => $admin->id,
        ]);

        return redirect()->route('products.index')->with('success', "Now impersonating {$user->name}");
    }

    /**
     * Stop customer impersonation and return to admin session.
     */
    public function stopImpersonate(Request $request)
    {
        if (! session('impersonate.active')) {
            return redirect()->route('products.index');
        }

        $adminId = session('impersonate.original_admin_id');
        $customerId = Auth::guard('web')->id();

        if ($adminId && $customerId) {
            $admin = Admin::find($adminId);
            $user = User::find($customerId);
            if ($admin && $user) {
                Log::channel('security')->info('Admin stopped impersonating customer', [
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                    'customer_id' => $user->id,
                    'customer_email' => $user->email,
                    'ip' => $request->ip(),
                    'timestamp' => now()->toIso8601String(),
                ]);
            }
        }

        Auth::guard('web')->logout();

        session()->forget([
            'impersonate.active',
            'impersonate.original_admin_id',
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Impersonation stopped.');
    }

    /**
     * Stateless authentication using onceUsingId (signed magic link).
     */
    public function magicLogin(Request $request, $id)
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Invalid or expired magic link.');
        }

        if (Auth::guard('web')->onceUsingId($id)) {
            $user = Auth::guard('web')->user();

            Log::channel('security')->info('User accessed site via magic link (onceUsingId)', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stateless authentication successful using onceUsingId for this request.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'is_authenticated_this_request' => Auth::guard('web')->check(),
            ]);
        }

        abort(401, 'Authentication failed.');
    }

    /**
     * Generate a signed magic link for a given user (for demonstration/testing).
     */
    public function generateMagicLink(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $url = URL::temporarySignedRoute(
            'magic-login',
            now()->addMinutes(15),
            ['id' => $id]
        );

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'expires_in' => '15 minutes',
            'magic_link' => $url,
        ]);
    }

    public function logoutAllDevices(Request $request)
    {
        $request->validateWithBag('logoutAll', [
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->password);

        return redirect()->route('products.index')->with('success', 'Logout successfull from all devices');
    }
}
