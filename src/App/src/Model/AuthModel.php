<?php

namespace App\Model;

use App\Entity\AutenticarEntity;
use App\Model\Mapper\AuthMapper;
use App\Model\Mapper\UsuarioMapper;
use App\Utils\SalvarLog;
use Exception;
use Laminas\Authentication\Storage\Session;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Session\SessionManager;

class AuthModel
{
    /** @var UsuarioMapper */
    private $usuarioMapper;

    /** @var AuthMapper */
    private $authMapper;

    /** @var SessionManager */
    private $sessionManager;
    
    /** @var SalvarLog */
    public $logger;

    public function __construct(
        public AdapterInterface $adapter
    ) {
        $this->authMapper = new AuthMapper($adapter);
        $this->usuarioMapper = new UsuarioMapper($adapter);
        $this->sessionManager = new SessionManager();
        $this->logger = new SalvarLog($adapter);
    }

    
    /**
     * Autentica o usuário e trata os dados necessário.
     * @param AutenticarEntity $dadosLogin
     * @param array $configJwt
     * @return array
     */
    public function autenticarUsuario(AutenticarEntity $dadosLogin, array $configJwt): array
    {
        $usuario = $this->authMapper->autenticarUsuario((array)$dadosLogin);
        if (empty($usuario)) {
            return [];
        }

        return [ 
            "usuario" => $this->gerarTokenJwt($usuario, $configJwt),
            "rfToken" => $this->gerarRefreshToken($configJwt)
        ];
    }

    
    /**
     * Requisita a validação do refresh token, para a geração de um novo token de acesso.
     * @param string $refreshToken
     * @param array $configJwt
     * @return array
     */
    public function gerarNovoTokenJwt(string $refreshToken, array $configJwt): array
    {
        $usuario = $this->validarRefreshToken($refreshToken, $configJwt);
        if (empty($usuario)) {
            return [];
        }

        return [
            "usuario" => $this->gerarTokenJwt($usuario, $configJwt),
            "rfToken" => $this->gerarRefreshToken($configJwt)
        ];
    }


    /**
     * Valida o token de acesso informado.
     * @param string $tokenJwt
     * @param array $config
     * @return array 
     */
    public function verificarTokenJwt(string $tokenJwt, array $configJwt): array
    {
        $result = [];
        $token = explode(".", $tokenJwt);
        if (count($token) !== 3) {
            return $result;
        }

        [$header, $payload, $assinatura] = $token;
        if ($assinatura !== $this->codificarAssinatura($header, $payload, $configJwt["key"])) {
            return $result;
        }
    
        try {
            $dadosUsuario = $this->decodificarToken($payload);
            $this->sessionManager->setId($dadosUsuario["jti"]);
            $session = new Session(null, null, $this->sessionManager);
            $expirado = date("Y-m-d H:i:s", $dadosUsuario["exp"]) > date("Y-m-d H:i:s");
            $audiencia = $dadosUsuario["aud"] === $configJwt["validAudience"];
            $issue = $dadosUsuario["iss"] === $configJwt["validIssue"];
            if (!$expirado || !$issue || !$audiencia || !$this->sessionManager->sessionExists() || $session->isEmpty()) {
                $session->isEmpty() && $this->sessionManager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
                return $result;
            }

            [$coUsuario, $email, $extensao] = explode("@", $this->decodificarToken($dadosUsuario["sub"])["id"]);
            $dadosArmazenados = $session->read();
            $dadosArmazenados["ds_email"] = $email . "@$extensao";
            unset($dadosArmazenados["rtid"]);
            $dadosReais = $this->usuarioMapper->resgatarDadosUsuario($coUsuario)[0] ?? [];
            if ($dadosArmazenados !== $dadosReais || empty($dadosReais)) {
                return $result;
            }

            $result = $dadosUsuario;
        } catch (Exception $error) {
            $this->logger->error("Não foi possível validar o usuário devido ao erro: {$error->getMessage()}");
            $this->sessionManager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
        }

        return $result;
    }


    /**
     * Valida se o token para re-autenticação está valido.
     * @param string $refreshToken
     * @param array $configJwt
     * @return array
     */
    public function validarRefreshToken(string $refreshToken, array $configJwt): array
    {
        $result = [];
        $token = explode(".", $refreshToken);
        if (count($token) !== 3) {
            return $result;
        }

        [$header, $payload, $assinatura] = $token;
        if ($assinatura !== $this->codificarAssinatura($header, $payload, $configJwt["key"])) {
            return $result;
        }

        try {
            $infosToken = $this->decodificarToken($payload);
            $this->sessionManager->setId($infosToken["sid"]);
            $session = new Session(null, null, $this->sessionManager);
            $audiencia = $infosToken["aud"] === $configJwt["validAudience"];
            $issue = $infosToken["iss"] === $configJwt["validIssue"];
            if (!$issue || !$audiencia || !$this->sessionManager->sessionExists() || $session->isEmpty()) {
                $session->isEmpty() && $this->sessionManager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
                return $result;
            }

            [$nome, $email, $extensao] = explode("@", $this->decodificarToken($infosToken["sub"])["user"]);
            $dadosArmazenados = $session->read();
            $dadosArmazenados["ds_nome"] = $nome;
            $dadosArmazenados["ds_email"] = "$email@$extensao";
            $rtid = $dadosArmazenados["rtid"] === $infosToken["rtid"];
            unset($dadosArmazenados["rtid"]);
            $dadosReais = $this->usuarioMapper->resgatarDadosUsuario($dadosArmazenados["co_usuario"])[0] ?? [];
            if ($dadosArmazenados !== $dadosReais || empty($dadosReais) || !$rtid) {
                $this->sessionManager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
                return $result;
            }

            $result = $dadosReais;
        } catch (Exception $error) {
            $this->logger->error("Não foi possível re-autenticar o usuário devido ao erro: {$error->getMessage()}");
            $this->sessionManager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
        }

        return $result;
    }

    
    /**
     * Gera um token JWT para a autenticação do usuário desejado.
     * @param array $informacoesUsuario
     * @param array $configJwt
     * @return string
     */
    public function gerarTokenJwt(array $informacoesUsuario, array $configJwt): string
    {
        $informacoesUsuario["sub"] = $this->codificarToken(json_encode(["id" => $informacoesUsuario["co_usuario"] . "@" . $informacoesUsuario["ds_email"]]));
        $informacoesUsuario["iat"] = time();
        $informacoesUsuario["nbf"] = time();
        $informacoesUsuario["exp"] = time() + 3600;
        $informacoesUsuario["jti"] = $this->sessionManager->getId();
        $informacoesUsuario["iss"] = $configJwt["validIssue"];
        $informacoesUsuario["aud"] = $configJwt["validAudience"];
        unset($informacoesUsuario["co_usuario"]);
        $header = $this->codificarToken(json_encode(["type" => "at+JWT", "alg" => "HS256"]));
        $payload = $this->codificarToken(json_encode($informacoesUsuario));
        $assinatura = $this->codificarAssinatura($header, $payload, $configJwt["key"]);
        return "$header.$payload.$assinatura";
    }


    /**
     * Gerar um token para servir de re-autenticação do usuário.
     * @param array $configJwt
     * @return string
     */
    public function gerarRefreshToken(array $configJwt): string
    {
        $session = new Session(null, null, $this->sessionManager);
        $informacoesUsuario = $session->read();
        $informacoesUsuario["rtid"] = uniqid() . "_" . time();
        $session->write($informacoesUsuario);
        $token = [
            "sub"  => $this->codificarToken(json_encode(["user" => $informacoesUsuario["ds_nome"] . "@" . $informacoesUsuario["ds_email"]])),
            "sid"  => $this->sessionManager->getId(),
            "rtid" => $informacoesUsuario["rtid"],
            "iss"  => $configJwt["validIssue"],
            "aud"  => $configJwt["validAudience"]
        ];
        $header = $this->codificarToken(json_encode(["type" => "rt+JWT", "alg" => "HS256"]));
        $payload = $this->codificarToken(json_encode($token));
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
     * @param string $header
     * @param string $payload
     * @param string $chave
     * @return string
     */
    private function codificarAssinatura(string $header, string $payload, string $chave): string
    {
        $assinatura = hash_hmac("sha256", "$header.$payload", $chave, true);
        return $this->codificarToken($assinatura);
    }


    /**
     * Decodifica o token jwt recebido.
     * @param string $token
     * @return array
     */
    private function decodificarToken(string $token): array
    {
        $resto = strlen($token) % 4;
        $resto !== 0 && $token .= str_repeat("=", 4 - $resto);
        $token = strtr($token, "-_", "+/");
        return json_decode(base64_decode($token), true);
    }
}