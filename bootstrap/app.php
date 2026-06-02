<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\PostTooLargeException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->hasHeader('X-Livewire')
                || $request->expectsJson(),
        );

        // Livewire file uploads expect JSON; when request body is too large,
        // Laravel's default behavior can be an HTML redirect, causing Livewire's JSON.parse to crash.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->hasHeader('X-Livewire')) {
                return response()->json([
                    'message' => 'File terlalu besar.',
                ], 413);
            }

            return null;
        });
    })->create();
