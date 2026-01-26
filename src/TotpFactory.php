<?php

namespace Dot\Totp;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class TotpFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): Totp
    {
        $options = $container->get('config')['dot_totp']['options'] ?? [];

        return new Totp(
            $options['period'] ?? null,
            $options['digits'] ?? null,
            $options['algorithm'] ?? null
        );
    }
}
