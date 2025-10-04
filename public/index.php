<?php

/**
 * Application Entry Point
 * 
 * This is the front controller for the application.
 * All requests are routed through this file.
 */

// Define application paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Load Composer's autoloader
require_once ROOT_PATH . '/vendor/autoload.php';

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->load();
} catch (Exception $e) {
    die('Error loading .env file: ' . $e->getMessage());
}

// Load application configuration
require_once APP_PATH . '/Config/app.php';

// Initialize application
try {
    $app = new App\Core\Application();
    $app->run();
} catch (Exception $e) {
    // Error handling
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo '<h1>Application Error</h1>';
        echo '<pre>' . $e->getMessage() . '</pre>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        echo 'An error occurred. Please try again later.';
    }
}

