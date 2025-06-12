<?php

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Please run "composer install" to install dependencies.');
}
require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

// Load configuration
$app->configure('app');
$app->configure('jwt');
$app->configure('redis');

// Register Redis facade
$app->singleton('redis', function () use ($app) {
    return new \Illuminate\Redis\RedisManager($app, 'predis', [
        'default' => [
            'host' => env('REDIS_HOST', 'redis'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
    ]);
});

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
*/

$app->configure('redis');
$app->configure('jwt');

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/

$app->register(App\Providers\AppServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Register Console Kernel
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
*/

$app->routeMiddleware([
    'jwt.auth' => App\Http\Middleware\JwtAuthMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Commands
|--------------------------------------------------------------------------
*/

$app->router->group([
    'namespace' => 'App\\Http\\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app; 