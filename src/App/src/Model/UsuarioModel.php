<?php

namespace App\Model;
use App\Entity\AtualizarUsuarioEntity;
use App\Entity\AutenticarEntity;
use App\Entity\CadastroUsuarioEntity;
use App\Model\Mapper\AuthMapper;
use App\Model\Mapper\UsuarioMapper;
use Exception;
use Laminas\Authentication\Storage\Session;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Session\SessionManager;

class UsuarioModel
{
    /** @var AuthModel */
    private $authModel;

    /** @var AuthMapper */
    private $authMapper;

    /** @var UsuarioMapper */
    private $usuarioMapper;

    public function __construct(
        public AdapterInterface $adapter
    ) {
        $this->authModel = new AuthModel($adapter);
        $this->authMapper = new AuthMapper($adapter);
        $this->usuarioMapper = new UsuarioMapper($adapter);
    }


    /**
     * Cadastra o usuário ao sistema, e autentica o mesmo.
     * @param CadastroUsuarioEntity $dadosUsuario
     * @param array $configJwt
     * @return array
     */
    public function cadastrarUsuario(CadastroUsuarioEntity $dadosUsuario, array $configJwt): array
    {
        $result = [];
        $dados = array_filter((array)$dadosUsuario);
        if (count($dados) !== 3) {
            return $result;
        }
        
        $dados["ds_senha"] = password_hash($dados["ds_senha"], PASSWORD_BCRYPT);
        $dados["dt_registro"] = date("Y-m-d H:i:s");
        $coUsuario = $this->usuarioMapper->cadastrarUsuario($dados);
        if ($coUsuario === 0) { 
            return $result;
        }

        $permissoes = $this->authMapper->alocarPermissaoUsuario(["co_usuario" => $coUsuario, "dt_registro" => $dados["dt_registro"]]);
        if (!$permissoes) {
            return $result;
        }

        $autenticarEntity = new AutenticarEntity();
        $autenticarEntity->ds_email = $dados["ds_email"];
        $autenticarEntity->ds_senha = $dadosUsuario->ds_senha;
        return $this->authModel->autenticarUsuario($autenticarEntity, $configJwt);
    }

        
    /**
     * Atualiza os dados do usuário desejado, e gera novos tokens atualizados.
     * @param AtualizarUsuarioEntity $dadosUsuario
     * @param array $configJwt
     * @return array
     */
    public function atualizarDadosUsuario(AtualizarUsuarioEntity $dadosUsuario, array $configJwt): array
    {
        $result = [];
        $sessionManager = new SessionManager();
        $usuarioArmazenado = (new Session(null, null, $sessionManager))->read();
        $dadosUsuario = array_filter((array)$dadosUsuario);
        if (empty($dadosUsuario)) {
            return $result;
        }

        $dadosUsuario["dt_registro"] = date("Y-m-d H:i:s");
        if (isset($dadosUsuario["ds_senha"])) {
            $dadosUsuario["ds_senha"] = password_hash($dadosUsuario["ds_senha"], PASSWORD_BCRYPT);
        }

        $atualizacao = $this->usuarioMapper->atualizarDadosUsuario($usuarioArmazenado["co_usuario"], $dadosUsuario);
        if (!$atualizacao) {
            return $result;
        }

        $usuario = $this->usuarioMapper->resgatarDadosUsuario($usuarioArmazenado["co_usuario"])[0];
        $sessionManager->regenerateId();
        (new Session(null, null, $sessionManager))->write($usuario);
        return [ 
            "usuario" => $this->authModel->gerarTokenJwt($usuario, $configJwt),
            "rfToken" => $this->authModel->gerarRefreshToken($configJwt)
        ];
    }

    /**
     * Finaliza a sessão do usuário.
     */
    public function finalizarSessaoUsuario(): void
    {   
        $sessionManager = new SessionManager();
        $sessionManager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
    }


    /**
     * Finaliza a sessão do usuário.
     */
    public function desativarUsuario(): bool
    {   
        $sessionManager = new SessionManager();
        $session = new Session(null, null, $sessionManager);
        $coUsuario = $session->read()["co_usuario"] ?? 0;
        if ($coUsuario === 0) {
            return false;
        }
        
        return $this->usuarioMapper->atualizarDadosUsuario($coUsuario, ["st_ativo" => "N"]);
    }
}