<?php

namespace App\Model\Mapper;
use Exception;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Select;
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
            $coUsuario = $tbData->getLastInsertValue();
            $this->alocarPermissaoUsuario(["co_usuario" => $coUsuario, "dt_registro" => $dadosUsuario["dt_registro"]]);
            $result = true;
        } catch (Exception $error) {
            // Cadastrar log de erro
            var_dump($error->getMessage()); //Remover ao implementar o log
        }

        return $result;
    }


    /**
     * @param int $coUsuario
     * @param array $dadosUsuario
     * @return bool
     */
    public function atualizarDadosUsuario(int $coUsuario, array $dadosUsuario): bool
    {
        $result = false;
        try {
            $tbData = new TableGateway("tb_usuarios", $this->adapter);
            $tbData->update($dadosUsuario, "co_usuario = $coUsuario");
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
            $authCallback->getDbSelect()->where("st_ativo = 'S'");
            $authCallback->setIdentity($dadosLogin["ds_email"])
                         ->setCredential($dadosLogin["ds_senha"]);

            $autenticado = $auth->authenticate($authCallback);
            if (!$autenticado->isValid()) {
                $this->manager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
                return $result;
            }

            $coUsuario = $authCallback->getResultRowObject("co_usuario")->co_usuario;
            $usuario = $this->resgatarDadosUsuario($coUsuario)[0];
            $auth->getStorage()->write($usuario);
            $result = $usuario;
            // Cadastrar log de usuário autenticado.
        } catch (Exception $error) {
            //Cadastrar log do erro gerado.
            var_dump($error->getMessage());exit; //Remover ao implementar o log.
        }

        return $result;
    }


    /**
     * Resgata as informações do usuário de acordo com seu id
     * @param int $coUsuario
     * @return array
     */
    public function resgatarDadosUsuario(int $coUsuario): array
    {
        $result = [];
        try {
            $tbData = new TableGateway("tb_usuarios", $this->adapter);
            $sql = $tbData->getSql()->select()
                                    ->columns(["co_usuario", "ds_nome", "ds_email", "dt_registro", "st_email_validado"])
                                    ->join("tb_grupo", "tb_grupo.co_usuario = tb_usuarios.co_usuario", ["dt_registro_permissao" => "dt_registro", "dt_limite_permissao" => "dt_limite"], Select::JOIN_LEFT)
                                    ->join("tb_permissoes", "tb_permissoes.co_permissao = tb_grupo.co_permissao", ["tp_permissao" => "ds_nome"], Select::JOIN_LEFT)
                                    ->where("tb_usuarios.co_usuario = $coUsuario AND tb_usuarios.st_ativo = 'S'");
            $result = $tbData->selectWith($sql)->toArray();
        } catch (Exception $error) {
            //Cadastrar log do erro gerado.
            var_dump($error->getMessage());exit; //Remover ao implementar o log.
        }

        return $result;
    }

    
    /**
     * Define o nivel de permissão do usuário.
     * Por padrão 1 - comum, caso um nivel desejado não seja informado, ou inexistente.
     * @param array $infosPermissao
     * @return bool
     */
    public function alocarPermissaoUsuario(array $infosPermissao): bool
    {
        $result = false;
        try {
            $coPermissao = (int)($infosPermissao["co_permissao"] ?? 0);
            $permissoes = array_column($this->resgatarPermissoes(), "ds_nome", "co_permissao");
            if (!isset($permissoes[$coPermissao])) {
                $infosPermissao["co_permissao"] = 1;
            }

            $tbData = new TableGateway("tb_grupo", $this->adapter);
            $tbData->insert($infosPermissao);
            $result = true;
        } catch (Exception $error) {
            //Cadastrar log do erro gerado.
            var_dump($error->getMessage());exit; //Remover ao implementar o log.
        }

        return $result;
    }

    /**
     * Resgata as permissões existente no sistema.
     * @return array
     */
    public function resgatarPermissoes(): array
    {
        $result = [];
        try {
            $tbData = new TableGateway("tb_permissoes", $this->adapter);
            $sql = $tbData->getSql()->select()->columns(["co_permissao", "ds_nome"]);
            $sql->where("st_ativo = 'S'");
            $result = $tbData->selectWith($sql)->toArray();
        } catch (Exception $error) {
            //Cadastrar log do erro gerado.
            var_dump($error->getMessage());exit; //Remover ao implementar o log.
        }

        return $result;
    }
}