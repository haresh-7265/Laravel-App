<?php

namespace App\Exceptions;

use Exception;

class ProductHasOrdersException extends Exception
{
    public function __construct()
    {
        parent::__construct('Product has order history and cannot be permanently deleted.');
    }

    public function render()
    {
        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $this->getMessage(),
            ], 422);
        }

        return back()->with('error', $this->getMessage());

    }
}
