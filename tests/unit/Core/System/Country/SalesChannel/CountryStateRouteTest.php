<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Country\SalesChannel;

use Cicada\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Cicada\Core\System\Country\Event\CountryStateCriteriaEvent;
use Cicada\Core\System\Country\SalesChannel\CountryStateRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('fundamentals@discovery')]
#[CoversClass(CountryStateRoute::class)]
class CountryStateRouteTest extends TestCase
{
    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());

        $this->salesChannelContext = Generator::createSalesChannelContext(
            baseContext: new Context(new SalesChannelApiSource(Uuid::randomHex())),
            salesChannel: $salesChannel
        );
    }

    public function testLoad(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(static::exactly(2))
            ->method('dispatch')
            ->with(static::callback(static function ($event) use (&$index) {
                switch ($index) {
                    case 0:
                        ++$index;
                        static::assertInstanceOf(AddCacheTagEvent::class, $event);

                        return true;
                    case 1:
                        ++$index;
                        static::assertInstanceOf(CountryStateCriteriaEvent::class, $event);

                        return true;
                    default:
                        static::fail('Unexpected event dispatched');
                }
            }));

        $countryStateRepository = $this->createMock(EntityRepository::class);
        $countryStateRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                'country_state',
                0,
                new CountryStateCollection(),
                null,
                new Criteria(),
                $this->salesChannelContext->getContext(),
            ));

        $route = new CountryStateRoute($countryStateRepository, $dispatcher);
        $route->load(Uuid::randomHex(), new Request(), new Criteria(), $this->salesChannelContext);
    }
}
