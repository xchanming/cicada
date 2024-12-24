<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigCollection;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigEntity;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfigSalesChannel\DocumentBaseConfigSalesChannelCollection;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfigSalesChannel\DocumentBaseConfigSalesChannelEntity;
use Cicada\Core\Checkout\Document\Service\DocumentConfigLoader;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(DocumentConfigLoader::class)]
class DocumentConfigLoaderTest extends TestCase
{
    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
    }

    public function testLoad(): void
    {
        $expectedCriteria = new Criteria();
        $expectedCriteria->addFilter(new EqualsFilter('documentType.technicalName', 'invoice'));
        $expectedCriteria->addAssociation('logo');
        $expectedCriteria->getAssociation('salesChannels')->addFilter(new EqualsFilter('salesChannelId', $this->ids->get('sales-channel-id')));

        $context = Context::createDefaultContext();

        $documentSalesChannel = new DocumentBaseConfigSalesChannelEntity();
        $documentSalesChannel->setUniqueIdentifier($this->ids->get('document-sales-channel'));
        $documentSalesChannel->setSalesChannelId($this->ids->get('sales-channel-id'));

        $document1 = new DocumentBaseConfigEntity();
        $document1->setId($this->ids->get('document-1'));
        $document1->setUniqueIdentifier($this->ids->get('document-1'));
        $document1->setGlobal(true);
        $document1->setSalesChannels(new DocumentBaseConfigSalesChannelCollection([$documentSalesChannel]));

        $document2 = new DocumentBaseConfigEntity();
        $document2->setId($this->ids->get('document-2'));
        $document2->setUniqueIdentifier($this->ids->get('document-2'));
        $document2->setGlobal(false);
        $document2->setSalesChannels(new DocumentBaseConfigSalesChannelCollection([$documentSalesChannel]));

        $result = new EntitySearchResult(
            'document_base_config',
            2,
            new DocumentBaseConfigCollection([$document1, $document2]),
            null,
            $expectedCriteria,
            $context
        );

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(static::once())
            ->method('search')
            ->with(static::equalTo($expectedCriteria), $context)
            ->willReturn($result);

        $loader = new DocumentConfigLoader($repo);
        $config = $loader->load('invoice', $this->ids->get('sales-channel-id'), $context);

        if (!isset($config->salesChannels)) {
            static::fail();
        }

        $salesChannels = $config->salesChannels;

        static::assertInstanceOf(DocumentBaseConfigSalesChannelCollection::class, $salesChannels);
        static::assertCount(1, $salesChannels);

        $salesChannel = $salesChannels->first();

        static::assertInstanceOf(DocumentBaseConfigSalesChannelEntity::class, $salesChannel);
        static::assertSame($this->ids->get('document-sales-channel'), $salesChannel->getUniqueIdentifier());
    }
}
