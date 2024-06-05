<?php

declare(strict_types=1);

namespace App\Middleware;
use App\Model\AuthModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiMiddleware implements MiddlewareInterface
{
    /** @var AuthModel */
    private $authModel;

    /** @var Acl */
    private $acl;

    public function __construct(
        private AdapterInterface $adapter,
        private RouterInterface $router,
        private array $config
    ) {
        $this->authModel = new AuthModel($adapter);
        $this->acl = new Acl();
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

        $this->alocarPermissoes();
        $url = $this->tratarUrl($request);
        $recurso = "{$url[0]}:{$url[1]}";
        $action = $url[2];

        if (!in_array($recurso, $this->acl->getResources()) || !$this->acl->isAllowed($payload["tp_permissao"], $recurso, $action)) { //alterar validação ao implementar permissões
            $result["flashMsg"] = "Não possui permissão para acessar este recurso!";
            return new JsonResponse($result);
        }

        return $handler->handle($request);
    }

    
    /**
     * Insere as permissões e privilegios no acl.
     * @return void
     */
    private function alocarPermissoes(): void
    {
        $this->alocarRecursos();
        $this->acl->addRole("comum");
        $this->acl->addRole("premium", "comum");
        
        $this->acl->allow(["comum"], ["app:usuario"], ["atualizar", "deslogar", "desativar"]);
    }


    /**
     * Aloca os recursos disponiveis no sistema
     * @return void
     */
    private function alocarRecursos(): void
    {
        $modulos = glob(getcwd() . "/src/*");
        foreach ($modulos as $modulo) {
            $nomeModulo = basename($modulo);
            $handlers = glob(getcwd() . "/src/$nomeModulo/src/Handler/*");
            foreach ($handlers as $handler) {
                if (!is_dir($handler)) {
                    continue;
                }

                $nomeModulo = strtolower($nomeModulo);
                $nomeHandler = strtolower(basename($handler));
                if ($this->acl->hasResource("$nomeModulo:$nomeHandler")) {
                    continue;
                }

                $this->acl->addResource("$nomeModulo:$nomeHandler");
            }
        }
    }


    /**
     * Resgata e trata a url para gerenciamento de recursos
     * @param ServerRequestInterface $request
     * @return array
     */
    private function tratarUrl(ServerRequestInterface $request): array
    {
        $url = explode('/', substr($request->getUri()->getPath(), 1));
        $modulo = preg_replace("/[^A-Za-z0-9- ]/", '', $url[0] ?? "");
        $handler = preg_replace("/[^A-Za-z0-9- ]/", '', $url[1] ?? "");
        $action = preg_replace("/[^A-Za-z0-9- ]/", '', $url[2] ?? "");
        return [$modulo, $handler, $action];
    }
}