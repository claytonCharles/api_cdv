<?php

declare(strict_types=1);

namespace App\Handler\Acesso;

use App\Model\AuthModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class RefreshTokenHandler implements RequestHandlerInterface
{
    /** @var AuthModel */
    public $authModel;

    public function __construct(
        public AdapterInterface $adapter,
        public array $config
    ) {
        $this->authModel = new AuthModel($adapter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dados = $request->getParsedBody() ?? [];
        $view = ["result" => false, "flashMsg" => "Refresh token invalido!"];
        $usuario = $this->authModel->gerarNovoTokenJwt($dados["rfToken"] ?? "", $this->config["jwt"]);
        if (empty($usuario)) {
            return new JsonResponse($view, 401);
        }

        $view = ["result" => true, "flashMsg" => "Re-autenticado com sucesso!", "usuario" => $usuario];
        return new JsonResponse($view);
    }
}