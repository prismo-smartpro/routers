<?php

namespace SmartPRO\Technology;

/**
 *
 */
class Routers
{
    /**
     * @var array
     */
    private array $routers;
    /**
     * @var string|mixed
     */
    private string $httpMethod;
    /**
     * @var string|null
     */
    private ?string $namespace;
    /**
     * @var string|mixed
     */
    private string $uri;
    /**
     * @var string
     */
    private string $group;
    /**
     * @var int|null
     */
    private ?int $error = null;


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
        if (!empty($this->group)) {
            $name = "/{$this->group}{$name}";
        }
        preg_match_all('~{(.*?)}~', $name, $data);
        $replaceName = preg_replace('~{(.*?)}~', '([^/]+)', $name);
        $replaceName = "/^" . str_replace('/', '\/', $replaceName) . "$/";
        if (!is_callable($handler)) {
            $handler = explode(":", $handler);
            $Controller = "\\" . $handler[0];
            $Action = $handler[1];
        } else {
            $isCallable = $handler;
        }
        $this->routers[$method][$replaceName] = [
            "router" => $name,
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
            $offset = 0;
            foreach ($this->routers[$this->httpMethod] as $key => $value) {
                $Action = $value['action'];
                preg_match_all($key, $this->uri, $results, PREG_SET_ORDER);
                if (!empty($results)) {
                    if (!empty($value['isCallable'])) {
                        $offset++;
                        call_user_func($value['isCallable'], [], $this);
                    } else {
                        $offset++;
                        unset($results[0][0]);
                        $data = array_combine($value['data'], $results[0]);
                        $className = $this->namespace . $value['handler'];
                        if (class_exists($className)) {
                            $Controller = new $className();
                            if (method_exists($Controller, $Action)) {
                                $Controller->$Action(["data" => $data ?? []]);
                            } else {
                                $this->error = self::NOT_IMPLEMENTED;
                            }
                        } else {
                            $this->error = self::NOT_FOUND;
                        }
                    }
                    break;
                }
            }
            if (empty($offset)) {
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
    public function group(string $group): void
    {
        $this->group = $group;
    }

    /**
     * @param string|null $namespace
     * @return void
     */
    public function setNamespace(?string $namespace): void
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