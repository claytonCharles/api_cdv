<?php

declare(strict_types=1);

namespace AppTest\Handler\Usuario;

use App\Handler\Usuario\AutenticarFactory;
use App\Handler\Usuario\AutenticarHandler;
use AppTest\InMemoryContainer;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class AutenticarHandlerTest extends TestCase
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
        $autenticarHandler = (new AutenticarFactory())($this->container);
        $response = $autenticarHandler->handle(
            self::createMock(ServerRequestInterface::class)
        );

        $jsonRespose = json_decode($response->getBody()->getContents());
        self::assertInstanceOf(AutenticarHandler::class, $autenticarHandler);
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertIsObject($jsonRespose);
        self::assertObjectHasProperty("result", $jsonRespose);
        self::assertEquals(false, $jsonRespose->result);
        self::assertObjectHasProperty("erros", $jsonRespose);
        self::assertNotEmpty($jsonRespose->erros);
    }
}
