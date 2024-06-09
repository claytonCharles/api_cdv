<?php

declare(strict_types=1);

namespace App\Handler\Usuario;

use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DesativarFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $adapter = $container->get(AdapterInterface::class);
        $config  = $container->get("config");
        return new DesativarHandler($adapter, $config);
    }
}