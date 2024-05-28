<?php

namespace App\Model\Mapper;
use Exception;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\Feature\EventFeature\TableGatewayEvent;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Session\SessionManager;

class AuthMapper
{
    /** @var SessionManager */
    private $manager;


    public function __construct(
        private AdapterInterface $adapter
    ) {
        $this->manager = new SessionManager();
    }

    /**
     * Cadastra o usuário ao sistema, e retorna o seu ID, caso o cadastro do mesmo ocorra com sucesso.
     * @param array $dadosUsuario
     * @return bool
     */
    public function cadastrarUsuario(array $dadosUsuario): bool
    {
        $result = false;
        try {
            $tbData = new TableGateway("tb_usuarios", $this->adapter);
            $tbData->insert($dadosUsuario);
            $result = true;
        } catch (Exception $error) {
            // Cadastrar log de erro
            var_dump($error->getMessage()); //Remover ao implementar o log
        }

        return $result;
    }

    /**
     * Autentica o usuário cadastrado no sistema.
     * @param array $dadosLogin
     * @return array
     */
    public function autenticarUsuario(array $dadosLogin): array
    {
        $result = [];
        
        try {
            $validarSenha = fn($hash, $senha) => password_verify($senha, $hash);
            $auth = new AuthenticationService();
            $authCallback = new CallbackCheckAdapter($this->adapter, "tb_usuarios", "ds_email", "ds_senha", $validarSenha);
            $authCallback->setIdentity($dadosLogin["ds_email"])
                         ->setCredential($dadosLogin["ds_senha"]);

            $autenticado = $auth->authenticate($authCallback);
            if (!$autenticado->isValid()) {
                $this->manager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
                return $result;
            }

            $usuario = (array)$authCallback->getResultRowObject(null, "ds_senha");
            $auth->getStorage()->write($usuario);
            $result = $usuario;
            // Cadastrar log de usuário autenticado.
        } catch (Exception $error) {
            //Cadastrar log do erro gerado.
            var_dump($error->getMessage());exit; //Remover ao implementar o log.
        }

        return $result;
    }
}