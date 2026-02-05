<?php

declare(strict_types=1);

namespace DotTest\Totp;

use Dot\Totp\TotpFactory;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class TotpFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokeReturnsTotpWithConfig(): void
    {
        $config = [
            'dot_totp' => [
                'options' => [
                    'period'    => 60,
                    'digits'    => 8,
                    'algorithm' => 'test',
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('config')->willReturn($config);

        $factory = new TotpFactory();
        $totp    = $factory($container);

        $this->assertSame(60, $totp->getPeriod());
        $this->assertSame(8, $totp->getDigits());
        $this->assertSame('test', $totp->getAlgorithm());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testInvokeReturnsTotpWithDefaults(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('config')->willReturn([]);

        $factory = new TotpFactory();
        $totp    = $factory($container);

        $this->assertSame(30, $totp->getPeriod());
        $this->assertSame(6, $totp->getDigits());
        $this->assertSame('sha1', $totp->getAlgorithm());
    }
}
