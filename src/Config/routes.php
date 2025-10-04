<?php

/**
 * Application Routes
 * 
 * Define all application routes here.
 */

use App\Core\Router;

/** @var Router $router */

// Home route
$router->get('/', 'HomeController@index');

// Page Classification API Routes
$router->get('/api/classify', 'PageClassifierController@classify');
$router->post('/api/classify', 'PageClassifierController@classify');
$router->post('/api/classify/batch', 'PageClassifierController@classifyBatch');
$router->get('/api/classify/test', 'PageClassifierController@test');
$router->get('/api/classify/help', 'PageClassifierController@help');

// Health check route
$router->get('/api/health', function() {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'OK',
        'timestamp' => time(),
    ]);
});

