<?php

namespace App\Model\Mapper;
use Exception;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\AdapterInterface;

class AuthMapper
{
    public function __construct(
        public AdapterInterface $adapter
    ) {

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
            $passwordValidador = fn($hash, $password) => password_verify($password, $hash);
            $auth = new AuthenticationService();
            $authCallback = new CallbackCheckAdapter($this->adapter, "users", "email", "password", $passwordValidador);
            $authCallback->setIdentity($dadosLogin["email"])
                         ->setCredential($dadosLogin["password"]);

            $autenticado = $auth->authenticate($authCallback);
            if (!$autenticado->isValid()) return $result;

            $usuario = (array)$authCallback->getResultRowObject(null, "password");
            $auth->getStorage()->write($usuario);
            $result = $usuario;
            // Cadastrar log de usuário autenticado.
        } catch (Exception $error) {
            //Cadastrar log do erro gerado.
            var_dump($error->getMessage());exit;
        }

        return $result;
    }
}