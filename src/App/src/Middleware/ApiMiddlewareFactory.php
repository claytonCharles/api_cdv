<?php

declare(strict_types=1);

namespace App\Middleware;
use Laminas\Db\Adapter\AdapterInterface;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

class ApiMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): MiddlewareInterface
    {
        $adapter = $container->get(AdapterInterface::class);
        $router  = $container->get(RouterInterface::class);
        $config  = $container->get("config");
        return new ApiMiddleware($adapter, $router, $config);
    }
}