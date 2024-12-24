<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\Health;

use Cicada\Core\Framework\SystemCheck\Check\SystemCheckExecutionContext;
use Cicada\Core\Kernel;
use Cicada\Storefront\Framework\SystemCheck\SaleChannelsReadinessCheck;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

/**
 * @internal
 */
#[CoversClass(SaleChannelsReadinessCheck::class)]
class SaleChannelsReadinessCheckTest extends TestCase
{
    private SaleChannelsReadinessCheck $salesChannelReadinessCheck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salesChannelReadinessCheck = new SaleChannelsReadinessCheck(
            $this->createMock(Kernel::class),
            $this->createMock(Router::class),
            $this->createMock(Connection::class),
            $this->createMock(RequestStack::class)
        );
    }

    public function testOnlyAllowedToRunInReadinessContexts(): void
    {
        foreach (SystemCheckExecutionContext::cases() as $context) {
            if (\in_array($context, SystemCheckExecutionContext::readiness(), true)) {
                continue;
            }

            static::assertFalse($this->salesChannelReadinessCheck->allowedToRunIn($context));
        }

        foreach (SystemCheckExecutionContext::readiness() as $context) {
            static::assertTrue($this->salesChannelReadinessCheck->allowedToRunIn($context));
        }
    }
}
