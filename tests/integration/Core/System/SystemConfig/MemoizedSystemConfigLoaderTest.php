<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\SystemConfig;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\System\SystemConfig\CachedSystemConfigLoader;
use Cicada\Core\System\SystemConfig\ConfiguredSystemConfigLoader;
use Cicada\Core\System\SystemConfig\MemoizedSystemConfigLoader;
use Cicada\Core\System\SystemConfig\SystemConfigLoader;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
class MemoizedSystemConfigLoaderTest extends TestCase
{
    use KernelTestBehaviour;

    public function testServiceDecorationChainPriority(): void
    {
        $service = static::getContainer()->get(SystemConfigLoader::class);

        static::assertInstanceOf(MemoizedSystemConfigLoader::class, $service);
        static::assertInstanceOf(ConfiguredSystemConfigLoader::class, $service->getDecorated());
        static::assertInstanceOf(CachedSystemConfigLoader::class, $service->getDecorated()->getDecorated());
        static::assertInstanceOf(SystemConfigLoader::class, $service->getDecorated()->getDecorated()->getDecorated());
    }
}
