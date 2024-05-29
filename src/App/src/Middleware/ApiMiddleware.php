<?php

declare(strict_types=1);

namespace App\Middleware;
use App\Model\AuthModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiMiddleware implements MiddlewareInterface
{
    /** @var AuthModel */
    private $authModel;

    public function __construct(
        private AdapterInterface $adapter,
        private RouterInterface $router,
        private array $config
    ) {
        $this->authModel = new AuthModel($adapter);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {   
        $result = ["result" => false, "flashMsg" => "Token de acesso invalido!"];
        $headers = getallheaders();
        $bearer = explode(" ", $headers["Authorization"] ?? "");
        if (empty($bearer) || count($bearer) !== 2 || $bearer[0] !== "Bearer") {
            return new JsonResponse($result, 401);
        }
        
        $payload = $this->authModel->verificarTokenJwt($bearer[1], $this->config["jwt"]);
        if (empty($payload)) {
            return new JsonResponse($result, 401);
        }
        
        var_dump($payload);exit;
        return $handler->handle($request);
    }
}