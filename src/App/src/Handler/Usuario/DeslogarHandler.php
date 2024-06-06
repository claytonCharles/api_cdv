<?php

declare(strict_types=1);

namespace App\Handler\Usuario;

use App\Model\UsuarioModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class DeslogarHandler implements RequestHandlerInterface
{
    /** @var UsuarioModel */
    public $usuarioModel;

    public function __construct(
        public AdapterInterface $adapter,
        public array $config
    ) {
        $this->usuarioModel = new UsuarioModel($adapter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $view = ["result" => true, "flashMsg" => "Sessão finalizada com sucesso!"];
        $this->usuarioModel->finalizarSessaoUsuario();
        return new JsonResponse($view);
    }
}