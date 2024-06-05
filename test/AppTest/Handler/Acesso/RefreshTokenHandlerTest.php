<?php

declare(strict_types=1);

namespace AppTest\Handler\Acesso;

use App\Handler\Acesso\RefreshTokenFactory;
use App\Handler\Acesso\RefreshTokenHandler;
use AppTest\InMemoryContainer;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RefreshTokenHandlerTest extends TestCase
{
    /** @var InMemoryContainer&MockObject */
    protected $container;

    protected function setUp(): void
    {
        $this->container = new InMemoryContainer();
        $this->container->setService(AdapterInterface::class, self::createMock(Adapter::class));
        $this->container->setService('config', ["jwt" => []]);
    }

    public function testeJsonResponseComFormularioIncorreto(): void
    {
        $refreshTokenHandler = (new RefreshTokenFactory())($this->container);
        $response = $refreshTokenHandler->handle(
            self::createMock(ServerRequestInterface::class)
        );

        $jsonRespose = json_decode($response->getBody()->getContents());
        self::assertInstanceOf(RefreshTokenHandler::class, $refreshTokenHandler);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertIsObject($jsonRespose);
        self::assertObjectHasProperty("result", $jsonRespose);
        self::assertEquals(false, $jsonRespose->result);
    }
}
