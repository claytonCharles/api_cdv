<?php

declare(strict_types=1);

namespace App\Handler\Usuario;

use App\Entity\AutenticarEntity;
use App\Model\AuthModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Form\Form;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class AutenticarHandler implements RequestHandlerInterface
{
    /** @var AttributeBuilder */
    public $attributeBuilder;

    /** @var AutenticarEntity */
    public $autenticarEntity;

    /** @var Form */
    public $formulario;

    /** @var AuthModel */
    public $authModel;

    public function __construct(
        public AdapterInterface $adapter,
        public array $config
    ) {
        $this->attributeBuilder = new AttributeBuilder();
        $this->autenticarEntity = new AutenticarEntity();
        $this->formulario = $this->attributeBuilder->createForm($this->autenticarEntity);
        $this->authModel = new AuthModel($adapter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dados = $request->getParsedBody() ?? [];
        $view = ["result" => false, "flashMsg" => "Não foi possivel autenticar o usuário!"];
        if (!$this->validarFormulario($dados)) {
            $view["erros"] = $this->formulario->getMessages();
            return new JsonResponse($view, 400);
        }

        $dadosLogin = $this->formulario->getData(FormInterface::VALUES_NORMALIZED);
        $usuario = $this->authModel->autenticarUsuario($dadosLogin, $this->config["jwt"]);
        if (empty($usuario)) {
            $view["flashMsg"] = "E-mail ou senha invalida.";
            return new JsonResponse($view, 401);
        }

        $view = ["result" => true, "flashMsg" => "Usuário autenticado com sucesso!", "usuario" => $usuario];
        return new JsonResponse($view);
    }


    /**
     * Valida de o os dados enviados estão de acordo com a entity.
     * @param array $dados
     * @return bool
     */
    public function validarFormulario(array $dados): bool
    {   
        $this->formulario->bind($this->autenticarEntity)->setData($dados);
        return $this->formulario->isValid();
    }
}
