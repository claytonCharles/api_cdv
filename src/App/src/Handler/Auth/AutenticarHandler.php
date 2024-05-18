<?php

declare(strict_types=1);

namespace App\Handler\Auth;

use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AutenticarHandler implements RequestHandlerInterface
{
    public function __construct(
        private string $containerName,
        private RouterInterface $router,
        private ?TemplateRendererInterface $template = null
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = ["result" => true, "flashMsg" => "Olá mundo!"];
        //Implementação do metodo de autenticação futuramente, criada agora para ajudar nos testes das configurações.
        return new JsonResponse($result);
    }
}
