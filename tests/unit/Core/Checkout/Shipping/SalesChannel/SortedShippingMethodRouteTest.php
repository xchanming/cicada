<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Shipping\SalesChannel;

use Cicada\Core\Checkout\Shipping\Hook\ShippingMethodRouteHook;
use Cicada\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Cicada\Core\Checkout\Shipping\SalesChannel\ShippingMethodRouteResponse;
use Cicada\Core\Checkout\Shipping\SalesChannel\SortedShippingMethodRoute;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(SortedShippingMethodRoute::class)]
class SortedShippingMethodRouteTest extends TestCase
{
    private MockObject&AbstractShippingMethodRoute $decorated;

    private MockObject&ScriptExecutor $executor;

    private SortedShippingMethodRoute $sortedRoute;

    private SalesChannelContext $context;

    private ShippingMethodRouteResponse $response;

    protected function setUp(): void
    {
        $this->decorated = $this->createMock(AbstractShippingMethodRoute::class);
        $this->executor = $this->createMock(ScriptExecutor::class);
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());
        $salesChannel->setShippingMethodId(Uuid::randomHex());
        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId($salesChannel->getShippingMethodId());
        $this->context = Generator::createSalesChannelContext(
            baseContext: new Context(new SalesChannelApiSource(Uuid::randomHex())),
            salesChannel: $salesChannel,
            shippingMethod: $shippingMethod,
        );
        $this->response = new ShippingMethodRouteResponse(
            new EntitySearchResult(
                'entity',
                1,
                new ShippingMethodCollection([$shippingMethod]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );
        $this->sortedRoute = new SortedShippingMethodRoute($this->decorated, $this->executor);
    }

    public function testTriggersScriptHookExecution(): void
    {
        $this->decorated
            ->expects(static::once())
            ->method('load')
            ->willReturn($this->response);

        $this->executor->method('execute')->with(static::callback(fn (ShippingMethodRouteHook $hook) => $hook->getCollection() === $this->response->getShippingMethods()
            && $hook->getSalesChannelContext() === $this->context
            && $hook->isOnlyAvailable()));

        $response = $this->sortedRoute->load(new Request(['onlyAvailable' => true]), $this->context, new Criteria());
        static::assertCount(1, $response->getShippingMethods());
    }
}
