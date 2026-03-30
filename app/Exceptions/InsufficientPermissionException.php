<?php
 
namespace App\Exceptions;
 
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
 
class InsufficientPermissionException extends Exception
{
    protected string $requiredPermission;
    protected string $action;
    protected ?string $resource;
 
    public function __construct(
        string $requiredPermission,
        string $action = '',
        ?string $resource = null,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->requiredPermission = $requiredPermission;
        $this->action = $action;
        $this->resource = $resource;
 
        if (! $message) {
            $parts = ["You do not have the '{$requiredPermission}' permission"];
            if ($action)   { $parts[] = "required to {$action}"; }
            if ($resource) { $parts[] = "on '{$resource}'"; }
            $message = implode(' ', $parts) . '.';
        }
 
        parent::__construct($message, $code, $previous);
    }
 
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success'             => false,
                'error'               => 'insufficient_permission',
                'message'             => $this->getMessage(),
                'required_permission' => $this->requiredPermission,
                'action'              => $this->action,
                'resource'            => $this->resource,
            ], 403);
        }
 
        return redirect()
            ->back()
            ->with('warning', $this->getMessage());
    }
 
    public function report(): void
    {
        $user = auth()->user();
 
        logger()->error('Insufficient permission — access denied', [
            'required_permission' => $this->requiredPermission,
            'action'              => $this->action,
            'resource'            => $this->resource,
            'user_id'             => $user?->id ?? 'guest',
            'ip'                  => request()->ip(),
            'method'              => request()->method(),
            'url'                 => request()->fullUrl(),
            'timestamp'           => now()->toIso8601String(),
        ]);
    }
}