<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->post("/login", App\Handler\Acesso\AutenticarHandler::class, "acesso.login");
    $app->post("/refresh-token", App\Handler\Acesso\RefreshTokenHandler::class, "acesso.refresh");
    $app->post("/cadastro", App\Handler\Usuario\CadastrarHandler::class, "usuario.cadastro");
    $app->post("/api/app/usuario/atualizar", App\Handler\Usuario\AtualizarHandler::class, "usuario.atualizar");
    $app->post("/api/app/usuario/deslogar", App\Handler\Usuario\DeslogarHandler::class, "usuario.deslogar");
    $app->post("/api/app/usuario/desativar", App\Handler\Usuario\DesativarHandler::class, "usuario.desativar");
};
