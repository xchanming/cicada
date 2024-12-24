<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ConfigJsonDefinition;
use Cicada\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ConfigJsonFieldTest extends TestCase
{
    use CacheTestBehaviour;
    use DataAbstractionLayerFieldTestBehaviour {
        tearDown as protected tearDownDefinitions;
    }
    use KernelTestBehaviour;

    private Connection $connection;

    private ConfigJsonDefinition $configJsonDefinition;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);

        $nullableTable = <<<EOF
DROP TABLE IF EXISTS _test_nullable;
CREATE TABLE `_test_nullable` (
  `id` varbinary(16) NOT NULL,
  `data` json NULL,
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY `id` (`id`)
);
EOF;
        $this->connection->executeStatement($nullableTable);
        $this->connection->beginTransaction();

        $definition = $this->registerDefinition(ConfigJsonDefinition::class);
        static::assertInstanceOf(ConfigJsonDefinition::class, $definition);
        $this->configJsonDefinition = $definition;
    }

    protected function tearDown(): void
    {
        $this->tearDownDefinitions();
        $this->connection->rollBack();
        $this->connection->executeStatement('DROP TABLE `_test_nullable`');
    }

    public function testFilter(): void
    {
        $context = WriteContext::createFromContext(Context::createDefaultContext());

        $stringId = Uuid::randomHex();
        $string = 'random string';

        $objectId = Uuid::randomHex();
        $object = [
            'foo' => 'bar',
        ];

        $data = [
            [
                'id' => $stringId,
                'data' => $string,
            ],
            [
                'id' => $objectId,
                'data' => $object,
            ],
        ];
        $this->getWriter()->insert($this->configJsonDefinition, $data, $context);

        $searcher = $this->getSearcher();
        $context = $context->getContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('data', $string));
        $result = $searcher->search($this->configJsonDefinition, $criteria, $context);

        static::assertCount(1, $result->getIds());
        static::assertEquals([$stringId], $result->getIds());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('data.foo', 'bar'));
        $result = $searcher->search($this->configJsonDefinition, $criteria, $context);

        static::assertCount(1, $result->getIds());
        static::assertEquals([$objectId], $result->getIds());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('data', 'not found'));
        $result = $searcher->search($this->configJsonDefinition, $criteria, $context);

        static::assertCount(0, $result->getIds());
    }

    private function getWriter(): EntityWriterInterface
    {
        return static::getContainer()->get(EntityWriter::class);
    }

    private function getSearcher(): EntitySearcherInterface
    {
        return static::getContainer()->get(EntitySearcherInterface::class);
    }
}
