<?php

namespace App\Model\Mapper;

use App\Utils\SalvarLog;
use Exception;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Session\SessionManager;

class AuthMapper
{
    /** @var UsuarioMapper */
    private $usuarioMapper;

    /** @var SalvarLog */
    public $logger;

    public function __construct(
        private AdapterInterface $adapter
    ) {
        $this->usuarioMapper = new UsuarioMapper($adapter);
        $this->logger = new SalvarLog($adapter);
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
                (new SessionManager())->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
                return $result;
            }

            $coUsuario = $authCallback->getResultRowObject("co_usuario")->co_usuario;
            $usuario = $this->usuarioMapper->resgatarDadosUsuario($coUsuario)[0];
            $auth->getStorage()->write($usuario);
            $result = $usuario;
            $this->logger->info("Usuário de código '{$usuario["co_usuario"]}' e nome '{$usuario["ds_nome"]}' acaba se acessar o sistema!");
        } catch (Exception $error) {
            $this->logger->error("Não foi possível autenticar o usuário devido ao erro: {$error->getMessage()}");
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

            $tbData = new TableGateway("tb_grupos", $this->adapter);
            $tbData->insert($infosPermissao);
            $result = true;
        } catch (Exception $error) {
            $this->logger->error("Não foi possível Alocar as permissões do Usuário devido ao erro: {$error->getMessage()}");
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
            $this->logger->error("Não foi possível resgatar as permissões do sistema devido ao erro: {$error->getMessage()}");
        }

        return $result;
    }
}