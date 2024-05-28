<?php

namespace App\Model;
use App\Entity\AutenticarEntity;
use App\Entity\CadastroUsuarioEntity;
use App\Model\Mapper\AuthMapper;
use DateTimeInterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Session\SessionManager;

class AuthModel
{

    /** @var AuthMapper */
    public $authMapper;

    public function __construct(
        public AdapterInterface $adapter
    ) {
        $this->authMapper = new AuthMapper($adapter);
    }

    
    /**
     * Cadastra o usuário ao sistema, e autentica o mesmo.
     * @param CadastroUsuarioEntity $dadosUsuario
     * @param array $configJwt
     * @return string
     */
    public function cadastrarUsuario(CadastroUsuarioEntity $dadosUsuario, array $configJwt): string
    {
        $result = "";
        $dados = (array)$dadosUsuario;
        $dados["ds_senha"] = password_hash($dados["ds_senha"], PASSWORD_BCRYPT);
        $dados["dt_registro"] = date("Y-m-d H:i:s");
        $coUsuario = $this->authMapper->cadastrarUsuario($dados);
        if (!$coUsuario) return $result;

        $autenticarEntity = new AutenticarEntity();
        $autenticarEntity->ds_email = $dados["ds_email"];
        $autenticarEntity->ds_senha = $dadosUsuario->ds_senha;
        $result = $this->autenticarUsuario($autenticarEntity, $configJwt);
        return $result;
    }


    /**
     * Autentica o usuário e trata os dados necessário.
     * @param AutenticarEntity $dadosLogin
     * @param array $configJwt
     * @return string
     */
    public function autenticarUsuario(AutenticarEntity $dadosLogin, array $configJwt): string
    {
        $result = "";
        $usuario = $this->authMapper->autenticarUsuario((array)$dadosLogin);
        if (empty($usuario)) return $result;
        
        $result = $this->gerarTokenJwt($usuario, $configJwt);
        return $result;
    }

    
    /**
     * Gera um token JWT para a autenticação do usuário desejado.
     * @param array $informacoesUsuario
     * @param array $configJwt
     * @return string
     */
    private function gerarTokenJwt(array $informacoesUsuario, array $configJwt): string
    {
        $session = new SessionManager();
        $informacoesUsuario["sub"] = $this->codificarToken(json_encode($informacoesUsuario["co_usuario"] . "@" . $informacoesUsuario["ds_email"]));
        $informacoesUsuario["iat"] = time();
        $informacoesUsuario["nbf"] = time();
        $informacoesUsuario["exp"] = time() + 3600;
        $informacoesUsuario["jti"] = $session->getId();
        $informacoesUsuario["iss"] = $configJwt["validIssue"];
        $informacoesUsuario["aud"] = $configJwt["validAudience"];
        unset($informacoesUsuario["co_usuario"]);
        $header = $this->codificarToken(json_encode(["type" => "at+JWT", "alg" => "HS256"]));
        $payload = $this->codificarToken(json_encode($informacoesUsuario));
        $assinatura = $this->codificarAssinatura($header, $payload, $configJwt["key"]);
        return "$header.$payload.$assinatura";
    }


    /**
     * Faz o encode dos componentes do JWT
     * @param string $dados
     * @return string
     */
    private function codificarToken(string $dados): string
    {
        return rtrim(strtr(base64_encode($dados), "+/", "-_"), "=");
    }


    /**
     * Faz a codificaçao da assinatura para o token jwt.
     */
    private function codificarAssinatura(string $header, string $payload, string $chave): string
    {
        $assinatura = hash_hmac("sha256", "$header.$payload", $chave, true);
        return $this->codificarToken($assinatura);
    }


    /**
     * Decodifica o token jwt recebido.
     * @param string $token
     * @return string
     */
    private function decodificarToken(string $token): string
    {
        $resto = strlen($token) % 4;
        $resto !== 0 && $token .= str_repeat("=", 4 - $resto);
        $token = strtr($token, "-_", "+/");
        return json_decode(base64_decode($token), true);
    }
}