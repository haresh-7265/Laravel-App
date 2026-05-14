<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EmptyCartException extends Exception
{
 
    public function __construct(
        string $message = 'cart is empty',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        
        parent::__construct($message, $code, $previous);
    }
 
    // response
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status'            => 'warning',
                'error'              => 'cart is empty',
                'message'            => $this->getMessage(),
            ], 400);
        }
 
        return redirect()
            ->route('cart.index')
            ->with('warning', $this->getMessage());
    }
}