## Introdução

Sistema de rotas Smart Technology

## Instalação

```bash
composer require prismo-smartpro/router
```

## .htaccess
```apacheconf
RewriteEngine On
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=/$1 [L,QSA]
```

## Exemplo de como usar

```php
<?php

require "vendor/autoload.php";

use SmartPRO\Technology\Routers;
use SmartPRO\Technology\Middleware;

$router = new Routers();
$router->namespace("App\Controllers");
/*
 * ROTAS DO SITE
 */
$router->resetMiddlewares();
$router->get("/", "Client:home");
$router->get("/product/{code}/{slug}", "Client:product");
$router->get("/category/{name}", "Client:categories");
/*
 * ROTAS DO ADMIN
 */
$router->group("admin");
$router->get("/", "App:home");
$router->get("/users", "App:users");
$router->get("/register", function () {
    //...
});
/*
 * ROTAS DE API DO ADMIN
 */
$router->middleware([Middleware::class, "Auth"]);
$router->group("admin/api");
$router->post("/register", "App:register");
/*
 * DISPARA A ROTA
 */
$router->dispatch();
if (!empty($router->error())) {
    echo "<h1>Error: {$router->error()}</h1>";
}
```