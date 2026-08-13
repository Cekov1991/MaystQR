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
        // Laravel Cloud sits behind Cloudflare and its own load balancer, and
        // neither hop has an address we can enumerate. Without this the request
        // reports the load balancer as the client: scans record its IP and the
        // application does not see itself as being served over HTTPS.
        //
        // Trusting every proxy means X-Forwarded-For is only as honest as
        // whatever reached us, so request()->ip() is analytics, never a
        // security boundary.
        $middleware->trustProxies(at: '*');

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
