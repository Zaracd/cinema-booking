<?php
/*
 * Front controller: every request comes through this file.
 */
define('ROOT', dirname(__DIR__));

// When using PHP's built-in server, let it serve real files (CSS, JS, images) directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Load classes from app/ by namespace: App\Models\Movie -> app/Models/Movie.php
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\')) {
        $file = ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
require ROOT . '/app/Core/helpers.php';

date_default_timezone_set(config('app.timezone'));
ini_set('display_errors', config('app.debug') ? '1' : '0');
error_reporting(E_ALL);

session_set_cookie_params([
    'path'     => base_path() ?: '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_name('cinema_session');
session_start();

// Basic security headers.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

set_exception_handler(function (Throwable $e) {
    error_log((string) $e);
    if ($e instanceof PDOException && str_contains($e->getMessage(), 'Unknown database')) {
        abort(500, 'The database "' . config('db.name') . '" was not found. Import database/cinema_db.sql in phpMyAdmin first.');
    }
    if ($e instanceof PDOException && str_contains($e->getMessage(), '2002')) {
        abort(500, 'Cannot connect to MySQL. Start MySQL in the XAMPP Control Panel and check config/config.php.');
    }
    abort(500, config('app.debug') ? $e->getMessage() : null);
});

$router = new App\Core\Router();
require ROOT . '/app/routes.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base = base_path();
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
if ($path === '/index.php' || str_starts_with($path, '/index.php/')) {
    $path = substr($path, 10);
}
$router->dispatch($_SERVER['REQUEST_METHOD'], rawurldecode($path ?: '/'));
