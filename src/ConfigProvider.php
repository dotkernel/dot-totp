<?php

declare(strict_types=1);

namespace Dot\Totp;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    public function getDependencyConfig(): array
    {
        return [
            'factories' => [
                Totp::class => TotpFactory::class,
            ],
        ];
    }
}
