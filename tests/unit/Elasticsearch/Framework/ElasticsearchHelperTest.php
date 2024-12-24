<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework;

use OpenSearch\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Elasticsearch\Framework\DataAbstractionLayer\CriteriaParser;
use Cicada\Elasticsearch\Framework\ElasticsearchHelper;
use Cicada\Elasticsearch\Framework\ElasticsearchRegistry;

/**
 * @internal
 */
#[CoversClass(ElasticsearchHelper::class)]
class ElasticsearchHelperTest extends TestCase
{
    public function testLogAndThrowException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())->method('critical');
        $helper = new ElasticsearchHelper(
            'prod',
            true,
            true,
            'prefix',
            true,
            $this->createMock(Client::class),
            $this->createMock(ElasticsearchRegistry::class),
            $this->createMock(CriteriaParser::class),
            $logger
        );

        static::expectException(\RuntimeException::class);

        static::assertFalse($helper->logAndThrowException(new \RuntimeException('test')));
    }

    public function testLogAndThrowExceptionOnlyLogs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())->method('critical');
        $helper = new ElasticsearchHelper(
            'prod',
            true,
            true,
            'prefix',
            false,
            $this->createMock(Client::class),
            $this->createMock(ElasticsearchRegistry::class),
            $this->createMock(CriteriaParser::class),
            $logger
        );

        $helper->logAndThrowException(new \RuntimeException('test'));
    }

    public function testGetIndexName(): void
    {
        $helper = new ElasticsearchHelper(
            'prod',
            true,
            true,
            'prefix',
            true,
            $this->createMock(Client::class),
            $this->createMock(ElasticsearchRegistry::class),
            $this->createMock(CriteriaParser::class),
            $this->createMock(LoggerInterface::class)
        );

        static::assertSame('prefix_product', $helper->getIndexName(new ProductDefinition()));
    }

    public function testAllowSearch(): void
    {
        $registry = $this->createMock(ElasticsearchRegistry::class);
        $registry->method('has')->willReturnMap([
            ['product', true],
            ['category', false],
        ]);

        $helper = new ElasticsearchHelper(
            'prod',
            true,
            true,
            'prefix',
            true,
            $this->createMock(Client::class),
            $registry,
            $this->createMock(CriteriaParser::class),
            $this->createMock(LoggerInterface::class)
        );

        $criteria = new Criteria();
        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        static::assertTrue(
            $helper->allowSearch(new ProductDefinition(), Context::createDefaultContext(), $criteria)
        );

        static::assertFalse(
            $helper->allowSearch(new CategoryDefinition(), Context::createDefaultContext(), $criteria)
        );

        $helper->setEnabled(false);

        static::assertFalse(
            $helper->allowSearch(new ProductDefinition(), Context::createDefaultContext(), $criteria)
        );
    }
}
