<?php
 
namespace App\Exceptions;
 
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
 
class ProductOutOfStockException extends Exception
{
    protected string $productName;
    protected int $productId;
    protected int $requestedQuantity;
    protected int $availableQuantity;
 
    public function __construct(
        string $productName,
        int $productId,
        int $requestedQuantity = 0,
        int $availableQuantity = 0,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->productName = $productName;
        $this->productId = $productId;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableQuantity = $availableQuantity;
 
        $message = $message ?: "Product '{$productName}' is out of stock. "
            . "Requested: {$requestedQuantity}, Available: {$availableQuantity}.";
 
        parent::__construct($message, $code, $previous);
    }
 
    // response
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success'            => false,
                'error'              => 'product_out_of_stock',
                'message'            => $this->getMessage(),
                'product_id'         => $this->productId,
                'product_name'       => $this->productName,
                'requested_quantity' => $this->requestedQuantity,
                'available_quantity' => $this->availableQuantity,
            ], 422);
        }
 
        return redirect()
            ->back()
            ->with('warning', $this->getMessage());
    }
 
    /**
     * Report / log the exception with structured context.
     * Called automatically by Laravel's exception handler.
     */
    public function report(): void
    {
        Log::channel('product')->warning('Product out of stock attempted', [
            'product_id'         => $this->productId,
            'product_name'       => $this->productName,
            'requested_quantity' => $this->requestedQuantity,
            'available_quantity' => $this->availableQuantity,
            'user_id'            => auth()->id() ?? 'guest',
            'ip'                 => request()->ip(),
            'url'                => request()->fullUrl(),
            'timestamp'          => now()->toIso8601String(),
        ]);
    }
}