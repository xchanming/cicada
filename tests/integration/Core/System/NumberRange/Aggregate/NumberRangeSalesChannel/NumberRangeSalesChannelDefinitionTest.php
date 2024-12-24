<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\NumberRange\Aggregate\NumberRangeSalesChannel;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeSalesChannel\NumberRangeSalesChannelCollection;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeSalesChannel\NumberRangeSalesChannelEntity;
use Cicada\Core\System\NumberRange\NumberRangeCollection;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class NumberRangeSalesChannelDefinitionTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<NumberRangeCollection>
     */
    private EntityRepository $numberRangeRepository;

    /**
     * @var EntityRepository<SalesChannelCollection>
     */
    private EntityRepository $salesChannelRepository;

    protected function setUp(): void
    {
        $this->numberRangeRepository = static::getContainer()->get('number_range.repository');
        $this->salesChannelRepository = static::getContainer()->get('sales_channel.repository');
    }

    public function testNumberRangeSalesChannelCollectionFromNumberRange(): void
    {
        $numberRangeId = $this->createNumberRange();

        $criteria = new Criteria([$numberRangeId]);
        $criteria->addAssociation('numberRangeSalesChannels');

        $numberRange = $this->numberRangeRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($numberRange);
        $this->assertNumberRangeSalesChannel($numberRangeId, $numberRange->getNumberRangeSalesChannels());
    }

    public function testNumberRangeSalesChannelCollectionFromSalesChannel(): void
    {
        $numberRangeId = $this->createNumberRange();

        $criteria = new Criteria([TestDefaults::SALES_CHANNEL]);
        $criteria->addAssociation('numberRangeSalesChannels');

        $salesChannel = $this->salesChannelRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        static::assertNotNull($salesChannel);
        $this->assertNumberRangeSalesChannel($numberRangeId, $salesChannel->getNumberRangeSalesChannels());
    }

    private function createNumberRange(): string
    {
        $numberRangeId = Uuid::randomHex();

        $this->numberRangeRepository->create([[
            'id' => $numberRangeId,
            'name' => 'numberRange',
            'pattern' => '{n}',
            'start' => 0,
            'global' => false,
            'type' => [
                'id' => $numberRangeId,
                'typeName' => 'number range type',
                'technicalName' => 'number_range_type',
                'global' => false,
            ],
            'numberRangeSalesChannels' => [
                [
                    'numberRangeId' => $numberRangeId,
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'numberRangeTypeId' => $numberRangeId,
                ],
            ],
        ]], Context::createDefaultContext());

        return $numberRangeId;
    }

    private function assertNumberRangeSalesChannel(
        string $numberRangeId,
        ?NumberRangeSalesChannelCollection $getNumberRangeSalesChannels
    ): void {
        static::assertInstanceOf(NumberRangeSalesChannelCollection::class, $getNumberRangeSalesChannels);

        $numberRangeSalesChannel = $getNumberRangeSalesChannels->first();

        static::assertInstanceOf(NumberRangeSalesChannelEntity::class, $numberRangeSalesChannel);
        static::assertEquals($numberRangeId, $numberRangeSalesChannel->getNumberRangeId());
        static::assertEquals(TestDefaults::SALES_CHANNEL, $numberRangeSalesChannel->getSalesChannelId());
        static::assertEquals($numberRangeId, $numberRangeSalesChannel->getNumberRangeTypeId());
    }
}
