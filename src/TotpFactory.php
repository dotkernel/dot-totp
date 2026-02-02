<?php

declare(strict_types=1);

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

        $period    = isset($options['period']) ? (int) $options['period'] : 30;
        $digits    = isset($options['digits']) ? (int) $options['digits'] : 6;
        $algorithm = isset($options['algorithm']) ? (string) $options['algorithm'] : 'sha1';

        return new Totp($period, $digits, $algorithm);
    }
}
