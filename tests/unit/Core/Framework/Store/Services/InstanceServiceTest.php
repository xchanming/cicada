<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Services;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\InstanceService;
use Cicada\Core\Kernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InstanceService::class)]
class InstanceServiceTest extends TestCase
{
    public function testItReturnsInstanceIdIfNull(): void
    {
        $instanceService = new InstanceService(
            '6.4.0.0',
            null
        );

        static::assertNull($instanceService->getInstanceId());
    }

    public function testItReturnsInstanceIdIfSet(): void
    {
        $instanceService = new InstanceService(
            '6.4.0.0',
            'i-am-unique'
        );

        static::assertEquals('i-am-unique', $instanceService->getInstanceId());
    }

    public function testItReturnsSpecificCicadaVersion(): void
    {
        $instanceService = new InstanceService(
            '6.1.0.0',
            null
        );

        static::assertEquals('6.1.0.0', $instanceService->getCicadaVersion());
    }

    public function testItReturnsCicadaVersionStringIfVersionIsDeveloperVersion(): void
    {
        $instanceService = new InstanceService(
            Kernel::CICADA_FALLBACK_VERSION,
            null
        );

        static::assertEquals('___VERSION___', $instanceService->getCicadaVersion());
    }
}
