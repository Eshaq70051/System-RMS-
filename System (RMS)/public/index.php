<?php

declare(strict_types=1);

// Error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Autoload classes & helpers
require_once __DIR__ . '/../app/Helpers/functions.php';

spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Router;

$router = new Router();

// Load Web Routes
require_once __DIR__ . '/../routes/web.php';

// Dispatch Request
$router->dispatch();
