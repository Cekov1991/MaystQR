<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Laravel 11's bundled config triggers PHP 8.5 deprecation notices before the
// framework's error handler is registered; keep them out of the output.
error_reporting(E_ALL & ~E_DEPRECATED);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
