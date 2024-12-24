<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Cart;

use Cicada\Core\Content\Product\Cart\ProductGateway;
use Cicada\Core\Content\Product\Events\ProductGatewayCriteriaEvent;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(ProductGateway::class)]
class ProductGatewayTest extends TestCase
{
    public function testSendCriteriaEvent(): void
    {
        $ids = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $context = Generator::createSalesChannelContext();

        $repository = $this->createMock(SalesChannelRepository::class);
        $emptySearchResult = new EntitySearchResult(
            'product',
            0,
            new ProductCollection(),
            null,
            new Criteria(),
            $context->getContext()
        );
        $repository->method('search')->willReturn($emptySearchResult);

        $validator = static::callback(static fn ($subject) => $subject instanceof ProductGatewayCriteriaEvent);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(static::once())->method('dispatch')->with($validator);

        $gateway = new ProductGateway(
            $repository,
            $eventDispatcher
        );

        $gateway->get($ids, $context);
    }
}
