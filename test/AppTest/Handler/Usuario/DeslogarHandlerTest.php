<?php

declare(strict_types=1);

namespace AppTest\Handler\Acesso;

use App\Handler\Usuario\AtualizarFactory;
use App\Handler\Usuario\AtualizarHandler;
use App\Handler\Usuario\DeslogarFactory;
use App\Handler\Usuario\DeslogarHandler;
use AppTest\InMemoryContainer;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class DeslogarHandlerTest extends TestCase
{
    /** @var InMemoryContainer&MockObject */
    protected $container;

    protected function setUp(): void
    {
        $this->container = new InMemoryContainer();
        $this->container->setService(AdapterInterface::class, self::createMock(Adapter::class));
        $this->container->setService('config', []);
    }

    public function testeJsonResponseComFormularioIncorreto(): void
    {
        $deslogarHandler = (new DeslogarFactory())($this->container);
        $response = $deslogarHandler->handle(
            self::createMock(ServerRequestInterface::class)
        );

        $jsonRespose = json_decode($response->getBody()->getContents());
        self::assertInstanceOf(DeslogarHandler::class, $deslogarHandler);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertIsObject($jsonRespose);
        self::assertObjectHasProperty("result", $jsonRespose);
        self::assertEquals(true, $jsonRespose->result);
    }
}
