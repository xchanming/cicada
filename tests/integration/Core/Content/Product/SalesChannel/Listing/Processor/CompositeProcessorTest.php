<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel\Listing\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\CompositeListingProcessor;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(CompositeListingProcessor::class)]
class CompositeProcessorTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testComposition(): void
    {
        $request = new Request();
        $criteria = new Criteria();
        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $request->query->set('no-aggregations', true);
        static::getContainer()->get(CompositeListingProcessor::class)->prepare($request, $criteria, $context);
        static::assertEmpty($criteria->getAggregations());

        $request->query->set('only-aggregations', true);
        static::getContainer()->get(CompositeListingProcessor::class)->prepare($request, $criteria, $context);
        static::assertEmpty($criteria->getSorting());
        static::assertEmpty($criteria->getAssociations());
        static::assertSame(0, $criteria->getLimit());
        static::assertSame(Criteria::TOTAL_COUNT_MODE_NONE, $criteria->getTotalCountMode());
    }
}
