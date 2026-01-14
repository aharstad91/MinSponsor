<?php
/**
 * PSR-4 Autoloader for MinSponsor
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoloader class for MinSponsor namespace
 */
class Autoloader {
    
    /**
     * Namespace prefix
     */
    private const PREFIX = 'MinSponsor\\';
    
    /**
     * Base directory for namespace
     */
    private static string $base_dir;
    
    /**
     * Register the autoloader
     */
    public static function register(): void {
        self::$base_dir = __DIR__ . '/';
        spl_autoload_register([self::class, 'autoload']);
    }
    
    /**
     * Autoload callback
     *
     * @param string $class Fully qualified class name
     */
    public static function autoload(string $class): void {
        // Check if class uses our namespace prefix
        $prefix_length = strlen(self::PREFIX);
        if (strncmp(self::PREFIX, $class, $prefix_length) !== 0) {
            return;
        }
        
        // Get relative class name
        $relative_class = substr($class, $prefix_length);
        
        // Convert namespace separators to directory separators
        $file = self::$base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        // Load file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Register autoloader
Autoloader::register();
