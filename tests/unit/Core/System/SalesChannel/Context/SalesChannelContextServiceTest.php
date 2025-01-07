<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel\Context;

use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(SalesChannelContextService::class)]
class SalesChannelContextServiceTest extends TestCase
{
    public function testTokenExpired(): void
    {
        $factory = $this->createMock(SalesChannelContextFactory::class);
        $persister = $this->createMock(SalesChannelContextPersister::class);

        $service = new SalesChannelContextService(
            $factory,
            $this->createMock(CartRuleLoader::class),
            $persister,
            $this->createMock(CartService::class),
            $this->createMock(EventDispatcherInterface::class),
        );

        $persister->method('load')->willReturn(['expired' => true]);

        $expiredToken = Uuid::randomHex();

        $factory->expects(static::once())
            ->method('create')
            ->with(
                static::logicalNot(static::equalTo($expiredToken)),
                TestDefaults::SALES_CHANNEL,
                [
                    SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                    'expired' => true,
                ]
            )
            ->willReturn($this->createMock(SalesChannelContext::class));

        $service->get(new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, $expiredToken, Defaults::LANGUAGE_SYSTEM));
    }

    public function testTokenNotExpired(): void
    {
        $factory = $this->createMock(SalesChannelContextFactory::class);
        $persister = $this->createMock(SalesChannelContextPersister::class);

        $service = new SalesChannelContextService(
            $factory,
            $this->createMock(CartRuleLoader::class),
            $persister,
            $this->createMock(CartService::class),
            $this->createMock(EventDispatcherInterface::class)
        );

        $customerId = Uuid::randomHex();
        $persister->method('load')->willReturn(['expired' => false, SalesChannelContextService::CUSTOMER_ID => $customerId]);
        $noneExpiringToken = Uuid::randomHex();

        $factory->expects(static::once())
            ->method('create')
            ->with(
                $noneExpiringToken,
                TestDefaults::SALES_CHANNEL,
                [
                    SalesChannelContextService::LANGUAGE_ID => Defaults::LANGUAGE_SYSTEM,
                    SalesChannelContextService::CUSTOMER_ID => $customerId,
                    'expired' => false,
                ]
            )
            ->willReturn($this->createMock(SalesChannelContext::class));

        $service->get(new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, $noneExpiringToken, Defaults::LANGUAGE_SYSTEM));
    }

    public function testDispatchesSalesChannelContextCreatedEvent(): void
    {
        $token = 'test-token';
        $context = $this->createMock(SalesChannelContext::class);
        $session = [
            'foo' => 'bar',
        ];

        $persister = $this->createMock(SalesChannelContextPersister::class);
        $persister->method('load')->willReturn($session);

        $factory = $this->createMock(SalesChannelContextFactory::class);
        $factory->expects(static::once())
            ->method('create')
            ->with($token, TestDefaults::SALES_CHANNEL, $session)
            ->willReturn($context);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(static::once())
            ->method('dispatch')
            ->with(new SalesChannelContextCreatedEvent($context, $token, $session));

        $service = new SalesChannelContextService(
            $factory,
            $this->createMock(CartRuleLoader::class),
            $persister,
            $this->createMock(CartService::class),
            $dispatcher,
        );

        $service->get(new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, $token));
    }
}
