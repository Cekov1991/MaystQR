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
        // AgentaOS signs its webhooks with an HMAC over the raw body; it has no
        // CSRF token to present. Authenticity is proved by the signature check
        // in AgentaOsWebhookController, not by the session.
        $middleware->validateCsrfTokens(except: [
            'webhooks/agentaos',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
