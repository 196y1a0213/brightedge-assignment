<?php

namespace App\Core;

/**
 * Core Application Class
 * 
 * This is the main application class that bootstraps and runs the application.
 */
class Application
{
    protected $config;
    protected $router;

    public function __construct()
    {
        $this->loadConfig();
        $this->setTimezone();
        $this->initializeRouter();
    }

    /**
     * Load application configuration
     */
    protected function loadConfig()
    {
        $this->config = require APP_PATH . '/Config/app.php';
    }

    /**
     * Set application timezone
     */
    protected function setTimezone()
    {
        date_default_timezone_set($this->config['app']['timezone']);
    }

    /**
     * Initialize the router
     */
    protected function initializeRouter()
    {
        $this->router = new Router();
        $router = $this->router; // Make available in routes.php scope
        require APP_PATH . '/Config/routes.php';
    }

    /**
     * Run the application
     */
    public function run()
    {
        $this->router->dispatch();
    }

    /**
     * Get configuration value
     */
    public function config($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}

