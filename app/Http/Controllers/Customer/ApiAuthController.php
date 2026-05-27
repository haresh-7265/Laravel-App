<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string', // e.g. "iPhone 14", "Postman"
        ]);

        $customer = User::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $customer->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'customer' => $customer,
        ]);
    }

    public function logout(Request $request)
    {
        // revoke current token only
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out',
        ]);
    }

    public function tokens(Request $request)
    {
        // all tokens for device management
        $tokens = $request->user()->tokens()
            ->select('id', 'name', 'last_used_at', 'created_at')
            ->latest()
            ->get();

        return response()->json($tokens);
    }

    public function revokeToken(Request $request, $id)
    {
        // only revoke own tokens
        $request->user()->tokens()->where('id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Token revoked',
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => ['required', 'email', Rule::unique(User::class, 'email')],
            'password' => 'required|min:8|confirmed',
            'device_name' => 'required|string',
        ]);

        $customer = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $customer->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'customer' => $customer,
        ], 201);
    }
}
