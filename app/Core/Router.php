<?php
namespace App\Core;

/**
 * Tiny router. Routes look like  'GET', '/movies/{id}', [MovieController::class, 'show'].
 * {id} parts must be numbers and are passed to the action as arguments.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, array $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    private function add(string $method, string $path, array $action, array $middleware): void
    {
        $regex = '#^' . preg_replace('#\{[a-z_]+\}#', '(\d+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = compact('method', 'regex', 'action', 'middleware');
    }

    public function dispatch(string $method, string $path): void
    {
        $path = rtrim($path, '/') ?: '/';
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }

            if ($method === 'POST') {
                Csrf::verify();
            }
            foreach ($route['middleware'] as $mw) {
                if ($mw === 'auth') {
                    Auth::requireLogin();
                } elseif ($mw === 'admin') {
                    Auth::requireAdmin();
                } elseif ($mw === 'guest' && Auth::check()) {
                    redirect('/');
                }
            }

            [$class, $fn] = $route['action'];
            $args = array_map('intval', array_slice($m, 1));
            (new $class())->$fn(...$args);
            return;
        }

        abort($pathMatched ? 405 : 404);
    }
}
