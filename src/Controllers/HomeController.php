<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * HomeController
 * 
 * Handles home page and general application routes.
 */
class HomeController extends Controller
{
    /**
     * Display the home page
     */
    public function index()
    {
        $this->view('home');
    }
}

