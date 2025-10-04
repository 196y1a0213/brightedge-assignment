<?php

namespace App\Core;

/**
 * Base Controller Class
 * 
 * All controllers should extend this base controller.
 */
abstract class Controller
{
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Return view
     */
    protected function view($view, $data = [])
    {
        extract($data);
        $viewPath = APP_PATH . "/Views/{$view}.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new \Exception("View not found: {$view}");
        }
    }

    /**
     * Redirect to a URL
     */
    protected function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Validate request data
     */
    protected function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = explode('|', $fieldRules);

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && empty($data[$field])) {
                    $errors[$field][] = "The {$field} field is required.";
                }
                
                // Add more validation rules as needed
            }
        }

        return empty($errors) ? true : $errors;
    }
}

