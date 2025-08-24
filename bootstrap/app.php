<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Ajout des middlewares globaux pour les requêtes web
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Ajout des alias pour les middlewares
        $middleware->alias([
            'throttle.login' => ThrottleRequests::class,
            'admin' => AdminMiddleware::class,
            'superadmin' => SuperAdminMiddleware::class,
            'active' => \App\Http\Middleware\VerifyUserActif::class,
        ]);


    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configuration des exceptions (personnalisations si nécessaires)
    })
    ->create();
