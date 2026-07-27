<?php
/**
 * NCD 3D Print — front controller / bootstrap.
 * All requests are routed through this file.
 */

// ----- Configuration -----
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/database.php';

// ----- Secure session -----
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ----- Autoloader for core, helpers, models, controllers -----
spl_autoload_register(function (string $class): void {
    $paths = [
        __DIR__ . '/core/' . $class . '.php',
        __DIR__ . '/helpers/' . $class . '.php',
        __DIR__ . '/models/' . $class . '.php',
        __DIR__ . '/controllers/' . $class . '.php',
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Helper functions are plain functions, load them explicitly.
require __DIR__ . '/helpers/functions.php';

// ----- Dispatch -----
$router = new Router();
$router->dispatch($_GET['url'] ?? '');
