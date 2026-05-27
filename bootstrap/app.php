<?php

use App\Exceptions\Handler;
use App\Http\Middleware\RequestTrackingMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin-auth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->appendToGroup('web', [
            SetLocale::class,
            RequestTrackingMiddleware::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);
        $middleware->appendToPriorityList(
            RequestTrackingMiddleware::class,
            'auth'
        );

        $middleware->validateCsrfTokens(except: [
            '/submit',
            '/api/*',
        ]);

        // Redirect unauthenticated admin guard requests to /admin/login
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin/*') || $request->is('products/create', 'products/*/edit', 'products/trashed*', 'fakestore/*', 'products/export')) {
                return route('admin.login');
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

/*
|--------------------------------------------------------------------------
| Register Custom Exception Handler
|--------------------------------------------------------------------------
*/
$app->singleton(ExceptionHandler::class, Handler::class);

return $app;
