<?php

namespace SmartPRO\Technology;


use Exception;

class Routers
{
    /** @var array */
    private array $routers;
    /** @var string|mixed */
    private string $httpMethod;
    /** @var string|null */
    private ?string $namespace;
    /** @var string|mixed */
    private string $uri;
    /** @var string */
    private string $group;
    /** @var int|null */
    private ?int $error = null;
    protected ?string $errorString = null;
    protected ?array $route;
    protected ?array $middleware = null;


    const NOT_FOUND = 404;
    const NOT_IMPLEMENTED = 405;

    public function __construct()
    {
        $this->uri = $_GET['route'] ?? "/";
        $this->httpMethod = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @param $name
     * @param $handler
     * @param array $middleware
     * @return void
     */
    public function delete($name, $handler, array $middleware = array()): void
    {
        $this->addRouter("DELETE", $name, $handler, $middleware);
    }

    /**
     * @param $name
     * @param $handler
     * @param array $middleware
     * @return void
     */
    public function put($name, $handler, array $middleware = array()): void
    {
        $this->addRouter("PUT", $name, $handler, $middleware);
    }

    /**
     * @param $name
     * @param $handler
     * @param array $middleware
     * @return void
     */
    public function get($name, $handler, array $middleware = array()): void
    {
        $this->addRouter("GET", $name, $handler, $middleware);
    }

    /**
     * @param $name
     * @param $handler
     * @param array $middleware
     * @return void
     */
    public function post($name, $handler, array $middleware = array()): void
    {
        $this->addRouter("POST", $name, $handler, $middleware);
    }

    /**
     * @param $method
     * @param $name
     * @param $handler
     * @param array $middleware
     * @return void
     */
    private function addRouter($method, $name, $handler, array $middleware = array()): void
    {
        $name = empty($this->group) ? $name : "/{$this->group}{$name}";
        $name = rtrim($name, "/") ?: "/";

        preg_match_all('~{(.*?)}~', $name, $data);
        $replaceName = preg_replace('~{(.*?)}~', '([^/]+)', $name);
        $replaceName = "/^" . str_replace('/', '\/', $replaceName) . "$/";

        if (is_callable($handler)) {
            $isCallable = $handler;
            $Controller = $Action = null;
        } else {
            [$Controller, $Action] = explode(":", $handler) + [null, null];
            $Controller = "\\" . $Controller;
            $isCallable = null;
        }

        $this->routers[$method][$replaceName] = [
            "router" => $name,
            "namespace" => $this->namespace,
            "method" => $method,
            "handler" => $Controller,
            "action" => $Action,
            "data" => $data[1],
            "isCallable" => $isCallable,
            "middleware" => empty($middleware) ? $this->middleware : $middleware
        ];
    }


    public function dispatch(): void
    {
        try {
            $this->uri = rtrim($this->uri, "/") ?: "/";
            if (empty($this->routers[$this->httpMethod])) {
                throw new Exception("Routers does not exist", 404);
            }

            foreach ($this->routers[$this->httpMethod] as $key => $value) {
                if (preg_match_all($key, $this->uri, $results, PREG_SET_ORDER)) {
                    unset($results[0][0]);

                    $this->route = [
                        "results" => $results,
                        "values" => $value
                    ];
                    break;
                }
            }

            if (empty($this->route['values'])) {
                throw new Exception("Router does not exist", 404);
            }

            $middlewares = $this->route["values"]["middleware"];
            foreach ($middlewares as $middleware) {
                $middlewareClass = $middleware[0] ?? null;
                $middlewareMethod = $middleware[1] ?? null;

                if (empty($middlewareClass) or empty($middlewareMethod)) {
                    throw new Exception("Middleware data is invalid", 400);
                }

                if (!class_exists($middlewareClass)) {
                    throw new Exception("Middleware does not exist", 404);
                }

                if (!method_exists($middlewareClass, $middlewareMethod)) {
                    throw new Exception("Middleware not implemented", 405);
                }

                $carryMiddleware = new $middlewareClass();
                $executeMiddleware = $carryMiddleware->$middlewareMethod();
                if ($executeMiddleware === false) {
                    throw new Exception("Unsatisfactory request", 400);
                }
            }

            if (!empty($this->route['values']['isCallable']) && is_callable($this->route['values']['isCallable'])) {
                call_user_func($this->route['values']['isCallable'], [], $this);
                return;
            }

            $Action = $this->route['values']['action'] ?? null;
            $data = array_combine($this->route['values']['data'] ?? [], $this->route['results'][0] ?? []);
            $className = $this->route['values']['namespace'] . $this->route['values']['handler'];

            if (!class_exists($className)) {
                throw new Exception("Handle not implemented", 404);
            }

            $Controller = new $className();
            if (!method_exists($Controller, $Action)) {
                throw new Exception("Method does not exist", 405);
            }

            $Controller->$Action($data);

        } catch (Exception $exception) {
            $this->errorString = $exception->getMessage();
            $this->error = $exception->getCode();
        }
    }

    /**
     * @param string $group
     * @return Routers
     */
    public function group(string $group): Routers
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @param array|null $middleware
     */
    public function middleware(?array $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * @param string|null $namespace
     * @return void
     */
    public function namespace(?string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @return int|null
     */
    public function error(): ?int
    {
        return $this->error;
    }

    public function resetMiddlewares(): void
    {
        $this->middleware = [];
    }

    /**
     * @return string|null
     */
    public function errorString(): ?string
    {
        return $this->errorString;
    }
}