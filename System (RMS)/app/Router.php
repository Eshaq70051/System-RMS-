<?php

namespace App;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => '/' . trim($path, '/'),
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string
        if (false !== $pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        // Strip base path if running in subfolder
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($scriptDir !== '/' && strpos($requestUri, $scriptDir) === 0) {
            $requestUri = substr($requestUri, strlen($scriptDir));
        }

        $requestUri = '/' . trim($requestUri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Convert route path pattern to regex for parameters
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $requestUri, $matches)) {
                // Execute middlewares
                foreach ($route['middlewares'] as $mw) {
                    $this->runMiddleware($mw);
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_callable($route['handler'])) {
                    call_user_func_array($route['handler'], $params);
                    return;
                }

                if (is_array($route['handler'])) {
                    [$class, $method] = $route['handler'];
                    $controller = new $class();
                    call_user_func_array([$controller, $method], $params);
                    return;
                }
            }
        }

        // If no match found
        http_response_code(404);
        require_once __DIR__ . '/../views/errors/404.php';
    }

    private function runMiddleware(string $mw): void
    {
        if ($mw === 'auth') {
            if (!auth_check()) {
                set_flash('warning', 'يرجى تسجيل الدخول للوصول إلى هذه الصفحة');
                redirect('login');
            }
        } elseif (str_starts_with($mw, 'role:')) {
            $roles = explode(',', substr($mw, 5));
            if (!has_role(...$roles)) {
                http_response_code(403);
                require_once __DIR__ . '/../views/errors/403.php';
                exit;
            }
        }
    }
}
