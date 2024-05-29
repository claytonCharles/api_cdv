<?php

declare(strict_types=1);

namespace App\Handler\Usuario;

use App\Entity\CadastroUsuarioEntity;
use App\Model\AuthModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Form\Form;
use Laminas\Form\Annotation\AttributeBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class AtualizarHandler implements RequestHandlerInterface
{
    /** @var AttributeBuilder */
    public $attributeBuilder;

    /** @var CadastroUsuarioEntity */
    public $cadastroUsuarioEntity;

    /** @var Form */
    public $formulario;

    /** @var AuthModel */
    public $authModel;

    public function __construct(
        public AdapterInterface $adapter,
        public array $config
    ) {
        $this->attributeBuilder = new AttributeBuilder();
        $this->cadastroUsuarioEntity = new CadastroUsuarioEntity();
        $this->formulario = $this->attributeBuilder->createForm($this->cadastroUsuarioEntity);
        $this->authModel = new AuthModel($adapter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dados = $request->getParsedBody() ?? [];
        $view = ["result" => false, "flashMsg" => "Não foi possivel atualizar os dados do usuário!"];
        return new JsonResponse($view);
    }
}