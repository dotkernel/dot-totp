<?php

declare(strict_types=1);

namespace DotTest\Totp;

use Dot\Totp\ConfigProvider;
use Dot\Totp\Totp;
use Dot\Totp\TotpFactory;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvokeReturnsArrayWithDependencies(): void
    {
        $config = ($this->provider)();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertSame($this->provider->getDependencyConfig(), $config['dependencies']);
    }

    public function testGetDependencyConfigReturnsFactories(): void
    {
        $dependencies = $this->provider->getDependencyConfig();
        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey('factories', $dependencies);
        $this->assertArrayHasKey(Totp::class, $dependencies['factories']);
        $this->assertSame(TotpFactory::class, $dependencies['factories'][Totp::class]);
    }
}
