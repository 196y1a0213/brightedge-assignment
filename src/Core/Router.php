<?php

namespace App\Core;

/**
 * Router Class
 * 
 * Handles routing of HTTP requests to appropriate controllers.
 */
class Router
{
    protected $routes = [];
    protected $currentRoute = null;

    /**
     * Add a GET route
     */
    public function get($uri, $action)
    {
        $this->addRoute('GET', $uri, $action);
        return $this;
    }

    /**
     * Add a POST route
     */
    public function post($uri, $action)
    {
        $this->addRoute('POST', $uri, $action);
        return $this;
    }

    /**
     * Add a PUT route
     */
    public function put($uri, $action)
    {
        $this->addRoute('PUT', $uri, $action);
        return $this;
    }

    /**
     * Add a DELETE route
     */
    public function delete($uri, $action)
    {
        $this->addRoute('DELETE', $uri, $action);
        return $this;
    }

    /**
     * Add a route to the collection
     */
    protected function addRoute($method, $uri, $action)
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
        ];
    }

    /**
     * Dispatch the request to the appropriate controller
     */
    public function dispatch()
    {
        $uri = $this->getUri();
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($this->matches($route, $uri, $method)) {
                return $this->callAction($route['action']);
            }
        }

        // No route found - 404
        $this->abort(404);
    }

    /**
     * Check if the route matches the current request
     */
    protected function matches($route, $uri, $method)
    {
        if ($route['method'] !== $method) {
            return false;
        }

        $pattern = preg_replace('/\{[\w]+\}/', '([\w-]+)', $route['uri']);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Remove full match
            $this->currentRoute = [
                'route' => $route,
                'params' => $matches,
            ];
            return true;
        }

        return false;
    }

    /**
     * Get the current URI
     */
    protected function getUri()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return $uri;
    }

    /**
     * Call the route action
     */
    protected function callAction($action)
    {
        // If action is a callable
        if (is_callable($action)) {
            return call_user_func_array($action, $this->currentRoute['params']);
        }

        // If action is a string (Controller@method)
        if (is_string($action)) {
            list($controller, $method) = explode('@', $action);
            $controller = "App\\Controllers\\{$controller}";

            if (class_exists($controller)) {
                $controllerInstance = new $controller();
                if (method_exists($controllerInstance, $method)) {
                    return call_user_func_array(
                        [$controllerInstance, $method],
                        $this->currentRoute['params']
                    );
                }
            }
        }

        $this->abort(500, 'Invalid route action');
    }

    /**
     * Abort with error
     */
    protected function abort($code = 404, $message = '')
    {
        http_response_code($code);
        
        if ($code === 404) {
            echo '<h1>404 - Page Not Found</h1>';
        } else {
            echo "<h1>Error {$code}</h1>";
            if ($message) {
                echo "<p>{$message}</p>";
            }
        }
        
        exit;
    }
}

