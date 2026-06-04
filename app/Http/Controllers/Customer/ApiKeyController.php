<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * Render the dedicated API Keys view.
     */
    public function indexPage()
    {
        return view('customer.api-keys');
    }

    /**
     * List all API keys for the authenticated user.
     */
    public function index(Request $request)
    {
        $keys = $request->user()->apiKeys()
            ->select('id', 'name', 'last_used_at', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'api_keys' => $keys,
        ]);
    }

    /**
     * Create a new API key and return the plaintext token once.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Generate a secure random API key with prefix
        $plaintextKey = ApiKey::generateFor(auth()->user(), $request->name);

        return response()->json([
            'status' => 'success',
            'message' => 'API Key created successfully. Store this key safely as it will not be shown again.',
            'plain_text_key' => $plaintextKey,
        ], 201);
    }

    /**
     * Revoke/Delete an API key.
     */
    public function destroy(Request $request, $id)
    {
        $request->user()->apiKeys()->findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'API Key revoked successfully.',
        ]);
    }
}
