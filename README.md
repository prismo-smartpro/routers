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

$router = new Routers();
$router->setNamespace("App\Controllers");
/*
 * ROTAS DO SITE
 */
$router->get("/", "Client:home");
$router->get("/produto/{codigo}/{slug}", "Client:produto");
$router->get("/categoria/{name}", "Client:categorias");
/*
 * ROTAS DO ADMIN
 */
$router->group("admin");
$router->get("/", "App:home");
$router->get("/usuarios", "App:usuarios");
$router->get("/cadastrar", function () {
    //...
});
/*
 * ROTAS DE API DO ADMIN
 */
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