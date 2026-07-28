<?php
/**
 * NCD 3D Print — front controller / bootstrap.
 * All requests are routed through this file.
 */

// ----- Configuration -----
// config/config.php holds the database credentials and is deliberately NOT in
// version control, so a fresh deployment does not include it. Say so clearly
// instead of dying with a blank "HTTP 500".
if (!file_exists(__DIR__ . '/config/config.php')) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Setup required</title><style>'
       . 'body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:#f6f7f9;color:#1a1f27;'
       . 'display:grid;place-items:center;min-height:100vh;margin:0;padding:24px}'
       . '.c{background:#fff;border:1px solid #e6e8eb;border-radius:14px;padding:28px 30px;max-width:560px;'
       . 'box-shadow:0 1px 3px rgba(16,24,40,.08);line-height:1.7}'
       . 'h1{font-size:19px;margin:0 0 10px}code{background:#f2f4f7;padding:2px 6px;border-radius:5px;font-size:13px}'
       . 'ol{padding-left:20px;margin:12px 0 0}</style></head><body><div class="c">'
       . '<h1>Configuration file missing</h1>'
       . '<p>The application cannot start because <code>config/config.php</code> was not found on the server.</p>'
       . '<ol><li>Copy <code>config/config.sample.php</code> to <code>config/config.php</code>.</li>'
       . '<li>Fill in your database host, name, user and password.</li>'
       . '<li>Upload it to the <code>config/</code> folder (it is excluded from git on purpose, '
       . 'so automated deployments never overwrite or publish your credentials).</li></ol>'
       . '</div></body></html>';
    exit;
}

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
