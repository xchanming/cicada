<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SystemConfig\Service;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SystemConfig\Service\AppConfigReader;
use Cicada\Core\System\SystemConfig\Util\ConfigReader;
use Cicada\Core\Test\Stub\App\StaticSourceResolver;
use Cicada\Core\Test\Stub\Framework\Util\StaticFilesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AppConfigReader::class)]
class AppConfigReaderTest extends TestCase
{
    public function testReadConfigFromApp(): void
    {
        $app = (new AppEntity())->assign(['id' => Uuid::randomHex(), 'name' => 'TestApp']);

        $fs = new StaticFilesystem([
            'Resources/config/config.xml' => 'config',
        ]);
        $sourceResolver = new StaticSourceResolver(['TestApp' => $fs]);

        $configReader = $this->createMock(ConfigReader::class);
        $configReader->expects(static::once())
            ->method('read')
            ->with('/app-root/Resources/config/config.xml')
            ->willReturn([
                'config1' => 'value',
            ]);

        $appConfigReader = new AppConfigReader($sourceResolver, $configReader);
        static::assertSame(
            [
                'config1' => 'value',
            ],
            $appConfigReader->read($app)
        );
    }

    public function testReadConfigFromAppWhenItHasNone(): void
    {
        $app = (new AppEntity())->assign(['id' => Uuid::randomHex(), 'name' => 'TestApp']);

        $fs = new StaticFilesystem();

        $sourceResolver = new StaticSourceResolver(['TestApp' => $fs]);

        $configReader = $this->createMock(ConfigReader::class);
        $configReader->expects(static::never())->method('read');

        $appConfigReader = new AppConfigReader($sourceResolver, $configReader);
        static::assertNull($appConfigReader->read($app));
    }
}
