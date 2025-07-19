<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ProviderAdminMiddleware;
use App\Http\Middleware\ProviderMiddleware;
use App\Http\Middleware\UserMiddleware;
use App\Http\Middleware\UserProviderAdminMiddleware;
use App\Http\Middleware\UserProviderMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'                => AdminMiddleware::class,
            'provider'             => ProviderMiddleware::class,
            'user'                 => UserMiddleware::class,
            'user.provider.admin'  => UserProviderAdminMiddleware::class,
            'user.provider'        => UserProviderMiddleware::class,
            'provider.admin'       => ProviderAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
