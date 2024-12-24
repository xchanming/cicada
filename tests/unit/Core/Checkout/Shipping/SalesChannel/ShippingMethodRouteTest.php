<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Shipping\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\Test\Generator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingMethodRoute::class)]
class ShippingMethodRouteTest extends TestCase
{
    public function testGetDecorated(): void
    {
        $route = new ShippingMethodRoute($this->createMock(SalesChannelRepository::class), new EventDispatcher(), $this->createMock(ScriptExecutor::class));

        $this->expectException(DecorationPatternException::class);

        $route->getDecorated();
    }

    public function testLoad(): void
    {
        $request = new Request();
        $context = Generator::createSalesChannelContext();
        $criteria = new Criteria();

        $expectedCriteria = clone $criteria;
        $expectedCriteria->addFilter(new EqualsFilter('active', true));
        $expectedCriteria->addSorting(new FieldSorting('position'), new FieldSorting('name', FieldSorting::ASCENDING));
        $expectedCriteria->addAssociation('media');

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setUniqueIdentifier('foo');

        $result = new EntitySearchResult(
            'shipping_method',
            1,
            $entities = new ShippingMethodCollection([$shippingMethod]),
            null,
            $expectedCriteria,
            $context->getContext()
        );

        $repo = $this->createMock(SalesChannelRepository::class);
        $repo
            ->expects(static::once())
            ->method('search')
            ->with(static::equalTo($expectedCriteria), $context)
            ->willReturn($result);

        $route = new ShippingMethodRoute($repo, new EventDispatcher(), $this->createMock(ScriptExecutor::class));

        $response = $route->load($request, $context, $criteria);

        static::assertSame($entities, $response->getShippingMethods());
    }
}
