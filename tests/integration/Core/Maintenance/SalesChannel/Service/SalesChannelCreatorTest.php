<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Maintenance\SalesChannel\Service;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Maintenance\SalesChannel\Service\SalesChannelCreator;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;

/**
 * @internal
 */
#[Package('core')]
class SalesChannelCreatorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private SalesChannelCreator $salesChannelCreator;

    /**
     * @var EntityRepository<SalesChannelCollection>
     */
    private EntityRepository $salesChannelRepository;

    protected function setUp(): void
    {
        $this->salesChannelCreator = static::getContainer()->get(SalesChannelCreator::class);
        $this->salesChannelRepository = static::getContainer()->get('sales_channel.repository');
    }

    public function testCreateSalesChannel(): void
    {
        $id = Uuid::randomHex();
        $this->salesChannelCreator->createSalesChannel($id, 'test', Defaults::SALES_CHANNEL_TYPE_API);

        $salesChannel = $this->salesChannelRepository->search(new Criteria([$id]), Context::createDefaultContext())->getEntities()->first();

        static::assertNotNull($salesChannel);
        static::assertSame('test', $salesChannel->getName());
        static::assertSame(Defaults::SALES_CHANNEL_TYPE_API, $salesChannel->getTypeId());
    }
}
