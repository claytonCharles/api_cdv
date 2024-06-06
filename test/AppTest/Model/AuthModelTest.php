<?php

namespace AppTest\Model;
use App\Model\AuthModel;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Storage\Session;
use Laminas\Db\Adapter\Adapter;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class AuthModelTest extends TestCase
{
    /** @var array */
    protected $config;

    /** @var Adapter&&MockObject */
    protected $adapter;

    /** @var AuthModel */
    protected $authModel;

    protected function setUp(): void
    {
        $this->config = ["validAudience" => "teste", "validIssue" => "teste", "key" => "teste"];
        $this->adapter = self::createMock(Adapter::class);
        $this->authModel = new AuthModel($this->adapter);
    }

    public function testCriacaoTokenAcessoJwt(): void
    {
        $usuarioFake = ["co_usuario" => "1", "ds_email" => "teste@hotmail.com"];
        $result = $this->authModel->gerarTokenJwt($usuarioFake, $this->config);
        (new SessionManager())->destroy();

        self::assertIsString($result);
        self::assertEquals(3, count(explode(".", $result)));
    }

    public function testCriacaoRefreshTokenJwt(): void
    {
        $sessionManager = new SessionManager();
        $session = new Session(null, null, $sessionManager);
        $session->write(["ds_nome" => "teste", "ds_email" => "teste@hotmail.com"]);
        $result = $this->authModel->gerarRefreshToken($this->config);
        $sessionManager->destroy();

        self::assertIsString($result);
        self::assertEquals(3, count(explode(".", $result)));
    }
}