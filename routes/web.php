<?php

// Public routes
$router->get('/health', 'HealthController@check');
$router->post('/api/v1/auth/token', 'AuthController@token');

// Documentation routes
$router->get('/api/v1/docs', 'SwaggerController@docs');
$router->get('/api/v1/openapi.yaml', 'SwaggerController@yaml');

// Protected routes
$router->group(['middleware' => 'jwt.auth', 'prefix' => 'api/v1'], function () use ($router) {
    $router->post('/calculation', 'CalculationController@calculate');
    $router->post('/task', 'TaskController@upload');
    $router->get('/task/{taskId}', 'TaskController@status');
}); 