<?php

namespace App\Model;
use App\Entity\AutenticarEntity;
use App\Model\Mapper\AuthMapper;
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
     * Autentica o usuário e trata os dados necessário.
     * @param AutenticarEntity $dadosLogin
     * @return array
     */
    public function autenticarUsuario(AutenticarEntity $dadosLogin): array
    {
        $result = $this->authMapper->autenticarUsuario((array)$dadosLogin);
        return $result;
    }

    
    /**
     * Gera um token JWT para a autenticação do usuário desejado.
     * @param array $informacoesUsuario
     * @param array $infosJwt
     * @return string
     */
    public function gerarTokenJwt(array $informacoesUsuario, array $infosJwt): string
    {
        $session = new SessionManager();
        $informacoesUsuario["sub"] = $this->codificarToken(json_encode($informacoesUsuario["co_users"] . "@" . $informacoesUsuario["email"]));
        $informacoesUsuario["iat"] = time();
        $informacoesUsuario["nbf"] = time();
        $informacoesUsuario["exp"] = time() + 3600;
        $informacoesUsuario["jti"] = $session->getId();
        $informacoesUsuario["iss"] = $infosJwt["validIssue"];
        $informacoesUsuario["aud"] = $infosJwt["validAudience"];
        unset($informacoesUsuario["co_users"]);
        $header = $this->codificarToken(json_encode(["type" => "at+JWT", "alg" => "HS256"]));
        $payload = $this->codificarToken(json_encode($informacoesUsuario));
        $assinatura = $this->codificarAssinatura($header, $payload, $infosJwt["key"]);
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