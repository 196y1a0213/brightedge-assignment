<?php

namespace App\Utils;

/**
 * Logger Utility
 * 
 * Simple logger utility for logging application events.
 */
class Logger
{
    protected static $logPath;

    /**
     * Initialize logger with log file path
     */
    public static function init($logPath)
    {
        self::$logPath = $logPath;
        
        // Create log directory if it doesn't exist
        $dir = dirname(self::$logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Write log message
     */
    protected static function write($level, $message, $context = [])
    {
        if (!self::$logPath) {
            $config = require APP_PATH . '/Config/app.php';
            self::init(ROOT_PATH . '/' . $config['logging']['path']);
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents(self::$logPath, $logMessage, FILE_APPEND);
    }

    /**
     * Log debug message
     */
    public static function debug($message, $context = [])
    {
        self::write('DEBUG', $message, $context);
    }

    /**
     * Log info message
     */
    public static function info($message, $context = [])
    {
        self::write('INFO', $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning($message, $context = [])
    {
        self::write('WARNING', $message, $context);
    }

    /**
     * Log error message
     */
    public static function error($message, $context = [])
    {
        self::write('ERROR', $message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical($message, $context = [])
    {
        self::write('CRITICAL', $message, $context);
    }
}

