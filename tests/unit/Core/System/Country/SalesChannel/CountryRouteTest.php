<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Country\SalesChannel;

use Cicada\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryCollection;
use Cicada\Core\System\Country\Event\CountryCriteriaEvent;
use Cicada\Core\System\Country\SalesChannel\CountryRoute;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
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
#[CoversClass(CountryRoute::class)]
class CountryRouteTest extends TestCase
{
    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());

        $this->salesChannelContext = Generator::generateSalesChannelContext(
            baseContext: new Context(new SalesChannelApiSource(Uuid::randomHex())),
            salesChannel: $salesChannel
        );
    }

    public function testLoad(): void
    {
        $index = 0;
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
                        static::assertInstanceOf(CountryCriteriaEvent::class, $event);

                        return true;
                    default:
                        static::fail('Unexpected event dispatched');
                }
            }));

        $countryRepository = $this->createMock(SalesChannelRepository::class);
        $countryRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                'country',
                0,
                new CountryCollection(),
                null,
                new Criteria(),
                $this->salesChannelContext->getContext(),
            ));

        $route = new CountryRoute($countryRepository, $dispatcher);
        $route->load(new Request(), new Criteria(), $this->salesChannelContext);
    }
}
