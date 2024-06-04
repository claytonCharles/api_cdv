<?php

declare(strict_types=1);

namespace App\Handler\Usuario;

use App\Entity\AtualizarUsuarioEntity;
use App\Entity\CadastroUsuarioEntity;
use App\Model\AuthModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Form\Form;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use Laminas\Validator\Db\NoRecordExists;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class AtualizarHandler implements RequestHandlerInterface
{
    /** @var AttributeBuilder */
    public $attributeBuilder;

    /** @var AtualizarUsuarioEntity */
    public $atualizarUsuarioEntity;

    /** @var Form */
    public $formulario;

    /** @var AuthModel */
    public $authModel;

    public function __construct(
        public AdapterInterface $adapter,
        public array $config
    ) {
        $this->attributeBuilder = new AttributeBuilder();
        $this->atualizarUsuarioEntity = new AtualizarUsuarioEntity();
        $this->formulario = $this->attributeBuilder->createForm($this->atualizarUsuarioEntity);
        $this->authModel = new AuthModel($adapter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dados = $request->getParsedBody() ?? [];
        $view = ["result" => false, "flashMsg" => "Não foi possivel atualizar os dados do usuário!"];
        if (!$this->validarFormulario($dados)) {
            $view["erros"] = $this->formulario->getMessages();
            return new JsonResponse($view, 400);
        }

        $dadosUsuario = $this->formulario->getData(FormInterface::VALUES_NORMALIZED);
        $usuario = $this->authModel->atualizarDadosUsuario($dadosUsuario, $this->config["jwt"]);
        if (empty($usuario)) {
            return new JsonResponse($view, 400);
        }

        $view = ["result" => true, "flashMsg" => "Dados do usuário atualizados com sucesso!", "usuario" => $usuario];
        return new JsonResponse($view);
    }


    /**
     * Valida de o os dados enviados estão de acordo com a entity.
     * @param array $dados
     * @return bool
     */
    public function validarFormulario(array $dados): bool
    {   
        $this->formulario->bind($this->atualizarUsuarioEntity)->setData($dados);
        $validator = new NoRecordExists(["table" => "tb_usuarios", "field" => "ds_email", "adapter" => $this->adapter]);
        $email = $this->formulario->get("ds_email")->getValue();
        if (!$validator->isValid($email)) {
            $campoEmail = $this->formulario->getInputFilter()->get("ds_email");
            $campoEmail->getValidatorChain()->attach($validator->setMessage("E-mail já cadastrado!"));
        }

        return $this->formulario->isValid();
    }
}