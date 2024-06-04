<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->post('/', App\Handler\Usuario\AutenticarHandler::class, "login");
    $app->post("/cadastro", App\Handler\Usuario\CadastrarHandler::class, "cadastro.usuario");
    $app->post("/api/app/usuario/atualizar", App\Handler\Usuario\AtualizarHandler::class, "atualizar.usuario");
};
