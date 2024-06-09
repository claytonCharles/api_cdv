<?php

declare(strict_types=1);

namespace App\Handler\Usuario;

use App\Entity\CadastroUsuarioEntity;
use App\Model\UsuarioModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Form\Form;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Validator\Db\NoRecordExists;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class CadastrarHandler implements RequestHandlerInterface
{
    /** @var AttributeBuilder */
    public $attributeBuilder;

    /** @var CadastroUsuarioEntity */
    public $cadastroUsuarioEntity;

    /** @var Form */
    public $formulario;

    /** @var UsuarioModel */
    public $usuarioModel;

    public function __construct(
        public AdapterInterface $adapter,
        public array $config
    ) {
        $this->attributeBuilder = new AttributeBuilder();
        $this->cadastroUsuarioEntity = new CadastroUsuarioEntity();
        $this->formulario = $this->attributeBuilder->createForm($this->cadastroUsuarioEntity);
        $this->usuarioModel = new UsuarioModel($adapter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dados = $request->getParsedBody() ?? [];
        $view = ["result" => false, "flashMsg" => "Não foi possivel realizar o cadastro!"];
        if (!$this->validarFormulario($dados)) {
            $view["erros"] = $this->formulario->getMessages();
            return new JsonResponse($view, 400);
        }

        $dadosUsuario = $this->formulario->getData(FormInterface::VALUES_NORMALIZED);
        $usuario = $this->usuarioModel->cadastrarUsuario($dadosUsuario, $this->config["jwt"]);
        if (empty($usuario)) {
            return new JsonResponse($view, 401);
        }

        $view = ["result" => true, "flashMsg" => "Usuário cadastrado com sucesso!", "usuario" => $usuario];
        return new JsonResponse($view);
    }


    /**
     * Valida de o os dados enviados estão de acordo com a entity.
     * @param array $dados
     * @return bool
     */
    public function validarFormulario(array $dados): bool
    {   
        $this->formulario->bind($this->cadastroUsuarioEntity)->setData($dados);
        $validator = new NoRecordExists(["table" => "tb_usuarios", "field" => "ds_email", "adapter" => $this->adapter]);
        $email = $this->formulario->get("ds_email")->getValue();
        if (!$validator->isValid($email)) {
            $campoEmail = $this->formulario->getInputFilter()->get("ds_email");
            $campoEmail->getValidatorChain()->attach($validator->setMessage("E-mail já cadastrado!"));
        }

        return $this->formulario->isValid();
    }
}