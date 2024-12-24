<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\SalesChannel;

use Cicada\Core\Checkout\Payment\Hook\PaymentMethodRouteHook;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Cicada\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Cicada\Core\Checkout\Payment\SalesChannel\SortedPaymentMethodRoute;
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
#[CoversClass(SortedPaymentMethodRoute::class)]
class SortedPaymentMethodRouteTest extends TestCase
{
    private MockObject&AbstractPaymentMethodRoute $decorated;

    private MockObject&ScriptExecutor $executor;

    private SortedPaymentMethodRoute $sortedRoute;

    private SalesChannelContext $context;

    private PaymentMethodRouteResponse $response;

    protected function setUp(): void
    {
        $this->decorated = $this->createMock(AbstractPaymentMethodRoute::class);
        $this->executor = $this->createMock(ScriptExecutor::class);
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());
        $salesChannel->setPaymentMethodId(Uuid::randomHex());
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId($salesChannel->getPaymentMethodId());
        $this->context = Generator::createSalesChannelContext(
            baseContext: new Context(new SalesChannelApiSource(Uuid::randomHex())),
            salesChannel: $salesChannel,
            paymentMethod: $paymentMethod,
        );
        $this->response = new PaymentMethodRouteResponse(
            new EntitySearchResult(
                'entity',
                1,
                new PaymentMethodCollection([$paymentMethod]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );
        $this->sortedRoute = new SortedPaymentMethodRoute($this->decorated, $this->executor);
    }

    public function testTriggersScriptHookExecution(): void
    {
        $this->decorated
            ->expects(static::once())
            ->method('load')
            ->willReturn($this->response);

        $this->executor->method('execute')->with(static::callback(fn (PaymentMethodRouteHook $hook) => $hook->getCollection() === $this->response->getPaymentMethods()
            && $hook->getSalesChannelContext() === $this->context
            && $hook->isOnlyAvailable()));

        $response = $this->sortedRoute->load(new Request(['onlyAvailable' => true]), $this->context, new Criteria());
        static::assertCount(1, $response->getPaymentMethods());
    }
}
