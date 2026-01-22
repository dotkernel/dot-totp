<?php

namespace Dot\TOTP\Factory;

use Dot\TOTP\Middleware\AuthTOTPMiddleware;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AuthTOTPMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AuthTOTPMiddleware
    {
        return new AuthTOTPMiddleware(
            $container->get(RouterInterface::class),
            $container->get('config')
        );
    }
}
