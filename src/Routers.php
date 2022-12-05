<?php

namespace SmartPRO\Technology;


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

    protected ?array $route;


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
     * @return void
     */
    public function delete($name, $handler)
    {
        $this->addRouter("DELETE", $name, $handler);
    }

    /**
     * @param $name
     * @param $handler
     * @return void
     */
    public function put($name, $handler)
    {
        $this->addRouter("PUT", $name, $handler);
    }

    /**
     * @param $name
     * @param $handler
     * @return void
     */
    public function get($name, $handler)
    {
        $this->addRouter("GET", $name, $handler);
    }

    /**
     * @param $name
     * @param $handler
     * @return void
     */
    public function post($name, $handler)
    {
        $this->addRouter("POST", $name, $handler);
    }

    /**
     * @param $method
     * @param $name
     * @param $handler
     * @return void
     */
    private function addRouter($method, $name, $handler)
    {
        $name = empty($this->group) ? $name : "/{$this->group}{$name}";
        if ($name != "/") {
            $name = rtrim($name, "/");
        }
        preg_match_all('~{(.*?)}~', $name, $data);
        $replaceName = preg_replace('~{(.*?)}~', '([^/]+)', $name);
        $replaceName = "/^" . str_replace('/', '\/', $replaceName) . "$/";
        if (!is_callable($handler)) {
            $handler = explode(":", $handler);
            $Controller = "\\" . $handler[0] ?? null;
            $Action = $handler[1] ?? null;
        } else {
            $isCallable = $handler;
        }
        $this->routers[$method][$replaceName] = [
            "router" => $name,
            "namespace" => $this->namespace,
            "method" => $method,
            "handler" => $Controller ?? null,
            "action" => $Action ?? null,
            "data" => $data[1],
            "isCallable" => $isCallable ?? null
        ];
    }

    /**
     * @return void
     */
    public function dispatch()
    {
        if (!empty($this->routers[$this->httpMethod])) {
            foreach ($this->routers[$this->httpMethod] as $key => $value) {
                preg_match_all($key, $this->uri, $results, PREG_SET_ORDER);
                if (!empty($results)) {
                    unset($results[0][0]);
                    $this->route = [
                        "results" => $results,
                        "values" => $value
                    ];
                    break;
                }
            }

            if (!empty($this->route['values'])) {
                if (!empty($this->route['values']['isCallable'])
                    and is_callable($this->route['values']['isCallable'])) {
                    call_user_func($this->route['values']['isCallable'], [], $this);
                } else {
                    $Action = $this->route['values']['action'] ?? null;
                    $data = array_combine($this->route['values']['data'], $this->route['results'][0]);
                    $className = $this->route['values']['namespace'] . $this->route['values']['handler'];
                    if (class_exists($className)) {
                        $Controller = new $className();
                        if (method_exists($Controller, $Action)) {
                            $Controller->$Action($data ?? []);
                        } else {
                            $this->error = self::NOT_IMPLEMENTED;
                        }
                    } else {
                        $this->error = self::NOT_FOUND;
                    }
                }
            } else {
                $this->error = self::NOT_FOUND;
            }
        } else {
            $this->error = self::NOT_FOUND;
        }
    }

    /**
     * @param string $group
     * @return void
     */
    public function group(string $group): Routers
    {
        $this->group = $group;
        return $this;
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
}