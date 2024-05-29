<?php

namespace App\Model;
use App\Entity\AutenticarEntity;
use App\Entity\CadastroUsuarioEntity;
use App\Model\Mapper\AuthMapper;
use Exception;
use Laminas\Authentication\Storage\Session;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Session\SessionManager;

class AuthModel
{

    /** @var AuthMapper */
    private $authMapper;

    /** @var SessionManager */
    private $sessionManager;

    public function __construct(
        public AdapterInterface $adapter
    ) {
        $this->authMapper = new AuthMapper($adapter);
        $this->sessionManager = new SessionManager();
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
        if (!$coUsuario) { 
            return $result;
        }

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
     * @return array
     */
    public function autenticarUsuario(AutenticarEntity $dadosLogin, array $configJwt): array
    {
        $result = [];
        $usuario = $this->authMapper->autenticarUsuario((array)$dadosLogin);
        if (empty($usuario)) return $result;
        
        $result = [ 
            "usuario" => $this->gerarTokenJwt($usuario, $configJwt),
            "rfToken" => $this->gerarRefreshToken()
        ];
        return $result;
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
            if (!$expirado || !$issue || !$audiencia || !$this->sessionManager->sessionExists()) {
                return $result;
            }

            [$coUsuario, $email, $extensao] = explode("@", $this->decodificarToken($dadosUsuario["sub"])["id"]);
            $dadosArmazenados = $session->read();
            $dadosArmazenados["ds_email"] = $email . "@$extensao";
            unset($dadosArmazenados["rtid"]);
            $dadosReais = $this->authMapper->resgatarDadosUsuario($coUsuario);
            if (!in_array($dadosArmazenados, $dadosReais)) {
                return $result;
            }

            $result = $dadosUsuario;
        } catch (Exception $error) {
            //Cadastrar log do erro gerado.
            $this->sessionManager->destroy(["clear_storage" => true, "send_expire_cookie" => false]);
            var_dump($error->getMessage());exit;
        }

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
     * @return string
     */
    private function gerarRefreshToken(): string
    {
        $session = new Session(null, null, $this->sessionManager);
        $informacoesUsuario = $session->read();
        $informacoesUsuario["rtid"] = uniqid() . "_" . time();
        $session->write($informacoesUsuario);
        $token = [
            "sub" => $this->codificarToken(json_encode(["user" => $informacoesUsuario["ds_nome"] . "@" . $informacoesUsuario["ds_email"]])),
            "sid" => $this->sessionManager->getId(),
            "rtid" => $informacoesUsuario["rtid"]
        ];
        return $this->codificarToken(json_encode($token));
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