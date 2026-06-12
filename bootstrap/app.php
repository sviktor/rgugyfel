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
        // The JS-written cookie-consent cookie is plain JSON, not Laravel-
        // encrypted - except it so the Blade layer can read it (GA gate).
        $middleware->encryptCookies(except: [\App\Support\CookieConsent::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Email an uncaught exception (a genuine 500). Laravel 11 already skips
        // 404/403/validation/auth via Handler::$internalDontReport, so this only
        // fires for real server errors. ExceptionMailer is self-guarded (throttled,
        // try/catch) so it never throws from here.
        $exceptions->report(function (\Throwable $e) {
            \App\Support\ExceptionMailer::notify($e, request());
        });
    })->create();
