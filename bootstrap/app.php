<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'pin' => \App\Http\Middleware\EnsurePin::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\EnsurePin::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsurePinSet::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\AuditLogRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
