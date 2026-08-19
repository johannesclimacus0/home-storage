<?php

use App\Exceptions\Households\HouseholdAccessDenied;
use App\Exceptions\Households\HouseholdMembershipConflict;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HouseholdAccessDenied $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        });

        $exceptions->render(function (HouseholdMembershipConflict $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
