<?php
// app/Router.php
class Router {
  private array $routes = [
    'GET'  => [],
    'POST' => [],
  ];

  public function get(string $path, $handler): void  { $this->routes['GET' ][$this->norm($path)] = $handler; }
  public function post(string $path, $handler): void { $this->routes['POST'][$this->norm($path)] = $handler; }

  private function norm(string $p): string {
    $p = trim($p);
    $p = ltrim($p, '/');
    return $p === '' ? '' : rtrim($p, '/');
  }

  private function currentPath(): string {
    // Raw path
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $reqPath = ltrim($reqPath, '/');

    // Strip app base folder if any (e.g., /salesrecord)
    $baseUrlPath = parse_url(base_url(''), PHP_URL_PATH) ?: '/';
    $basePath    = trim($baseUrlPath, '/');
    if ($basePath !== '' && strncasecmp($reqPath, $basePath, strlen($basePath)) === 0) {
      $reqPath = ltrim(substr($reqPath, strlen($basePath)), '/');
    }

    return $this->norm($reqPath);
  }

  public function dispatch(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path   = $this->currentPath();

    $handler = $this->routes[$method][$path] ?? null;
    if ($handler === null) {
      http_response_code(404);
      echo 'Not Found';
      return;
    }

    // Support: "Controller@method" OR ['Controller','method']
    if (is_string($handler)) {
      if (strpos($handler, '@') === false) {
        throw new RuntimeException('Invalid route handler string. Use "Controller@method". Got: '.$handler);
      }
      [$class, $methodName] = explode('@', $handler, 2);
    } elseif (is_array($handler) && count($handler) === 2) {
      [$class, $methodName] = $handler;
    } else {
      throw new RuntimeException('Invalid route handler type for path: '.$path);
    }

    // Auto-require controller file if not loaded
    if (!class_exists($class)) {
      $file = __DIR__ . '/controllers/' . $class . '.php';
      if (!is_file($file)) {
        throw new RuntimeException("Controller file not found: {$file}");
      }
      require_once $file;
      if (!class_exists($class)) {
        throw new RuntimeException("Controller class {$class} not defined in {$file}");
      }
    }

    $controller = new $class();
    if (!method_exists($controller, $methodName)) {
      throw new RuntimeException("Method {$class}::{$methodName}() not found.");
    }

    // Call the controller action
    $controller->{$methodName}();
  }
}
