<?php

require_once __DIR__ . '/../handler/Response.php';

class Router
{
    private $routes = [];
    private $middlewares = [];

    public function add($method, $path, $handler)
    {
        $this->routes[] = [
            "method" => strtoupper($method),
            "path" => $path,
            "handler" => $handler
        ];
    }

    // helper methods
    public function get($path, $handler)
    {
        $this->add("GET", $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->add("POST", $path, $handler);
    }

    public function put($path, $handler)
    {
        $this->add("PUT", $path, $handler);
    }

    public function delete($path, $handler)
    {
        $this->add("DELETE", $path, $handler);
    }

    // middleware
    public function use($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    private function match($routePath, $uri)
    {
        $pattern = preg_replace('#\{([^/]+)\}#', '([^/]+)', $routePath);
        $pattern = "#^" . $pattern . "$#";

        if (preg_match($pattern, $uri, $matches)) {

            array_shift($matches);

            preg_match_all('#\{([^/]+)\}#', $routePath, $paramNames);

            $params = [];

            foreach ($paramNames[1] as $index => $name) {
                $params[$name] = $matches[$index];
            }

            return $params;
        }

        return false;
    }

    public function dispatch($method, $uri)
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {

            if ($route["method"] !== $method) {
                continue;
            }

            if ($route["path"] === $uri) {
                $params = [];
            } else {
                $params = $this->match($route["path"], $uri);
            }

            if ($params !== false) {

                foreach ($this->middlewares as $mw) {
                    call_user_func($mw);
                }

                $handler = $route["handler"];

                if (is_array($handler)) {
                    call_user_func_array($handler, array_values($params));
                } else {
                    call_user_func($handler);
                }

                return;
            }
        }

        Response::json([
            "error" => "route not found"
        ], 404);
    }
}