<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SystemConfig;

use Cicada\Core\System\SystemConfig\AbstractSystemConfigLoader;
use Cicada\Core\System\SystemConfig\ConfiguredSystemConfigLoader;
use Cicada\Core\System\SystemConfig\SymfonySystemConfigService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConfiguredSystemConfigLoader::class)]
class ConfiguredSystemConfigLoaderTest extends TestCase
{
    public function testDecoration(): void
    {
        $configLoader = $this->createMock(AbstractSystemConfigLoader::class);

        $config = new SymfonySystemConfigService(['default' => ['test.key' => 'true']]);

        $decorator = new ConfiguredSystemConfigLoader($configLoader, $config);

        $configLoader->expects(static::once())
            ->method('load')
            ->willReturn(['test' => ['key' => 'false']]);

        static::assertSame(['test' => ['key' => 'true']], $decorator->load(null));
    }
}
