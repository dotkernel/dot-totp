<?php

declare(strict_types=1);

namespace Dot\TOTP;

use Dot\TOTP\Factory\AuthTOTPMiddlewareFactory;
use Dot\TOTP\Middleware\AuthTOTPMiddleware;

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
            'factories'          => [
                AuthTOTPMiddleware::class => AuthTOTPMiddlewareFactory::class,
            ],
        ];
    }
}
