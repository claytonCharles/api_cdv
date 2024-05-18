<?php

declare(strict_types=1);

namespace App\Handler\Auth;

use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

class AutenticarFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $router = $container->get(RouterInterface::class);
        assert($router instanceof RouterInterface);
        return new AutenticarHandler($container::class, $router);
    }
}