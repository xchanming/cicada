<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field\Flag;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\WriteProtectedDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\WriteProtectedRelationDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\WriteProtectedTranslatedDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\WriteProtectedTranslationDefinition;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;

/**
 * @internal
 */
class WriteProtectedFlagTest extends TestCase
{
    use DataAbstractionLayerFieldTestBehaviour {
        tearDown as protected tearDownDefinitions;
    }
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);

        $this->connection->executeStatement('DROP TABLE IF EXISTS `_test_nullable`');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `_test_nullable_reference`');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `_test_nullable_translation`');
        $this->connection->executeStatement('DROP TABLE IF EXISTS `_test_relation`');

        $nullableTable = <<<EOF
CREATE TABLE `_test_relation` (
  `id` binary(16) NOT NULL,
  PRIMARY KEY `id` (`id`)
);

CREATE TABLE `_test_nullable_reference` (
  `wp_id` binary(16) NOT NULL,
  `relation_id` binary(16) NOT NULL,
  PRIMARY KEY `pk` (`wp_id`, `relation_id`)
);

CREATE TABLE `_test_nullable_translation` (
  `_test_nullable_id` binary(16) NOT NULL,
  `language_id` binary(16) NOT NULL,
  `protected` varchar(255) NULL,
  `system_protected` varchar(255) NULL,
  PRIMARY KEY `pk` (`_test_nullable_id`, `language_id`)
);

CREATE TABLE `_test_nullable` (
  `id` binary(16) NOT NULL,
  `relation_id` binary(16) NULL,
  `system_relation_id` binary(16) NULL,
  `protected` varchar(255) NULL,
  `system_protected` varchar(255) NULL,
  PRIMARY KEY `id` (`id`),
  FOREIGN KEY `fk` (`relation_id`) REFERENCES _test_relation (`id`)
);
EOF;
        $this->connection->executeStatement($nullableTable);
        $this->connection->beginTransaction();

        $this->registerDefinition(
            WriteProtectedTranslatedDefinition::class,
            WriteProtectedTranslationDefinition::class,
            WriteProtectedDefinition::class,
            WriteProtectedRelationDefinition::class
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDefinitions();
        $this->connection->rollBack();

        $this->connection->executeStatement('DROP TABLE `_test_nullable`');
        $this->connection->executeStatement('DROP TABLE `_test_relation`');
        $this->connection->executeStatement('DROP TABLE `_test_nullable_translation`');
        $this->connection->executeStatement('DROP TABLE `_test_nullable_reference`');

        parent::tearDown();
    }

    public function testWriteWithoutPermission(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'protected' => 'foobar',
        ];

        $ex = null;

        try {
            $this->getWriter()->insert($definition, [$data], $context);
        } catch (WriteException $ex) {
        }

        static::assertInstanceOf(WriteException::class, $ex);
        static::assertCount(1, $ex->getExceptions());
        static::assertEquals('This field is write-protected.', $this->getValidationExceptionMessage($ex));

        $fieldException = $ex->getExceptions()[0];
        static::assertEquals(WriteConstraintViolationException::class, $fieldException::class);
        static::assertInstanceOf(WriteConstraintViolationException::class, $fieldException);
        static::assertEquals('/0/protected', $fieldException->getPath());
    }

    public function testWriteWithoutProtectedField(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
        ];

        $this->getWriter()->insert($definition, [$data], $context);

        $data = $this->connection->fetchAllAssociative('SELECT * FROM `_test_nullable`');

        static::assertCount(1, $data);
        static::assertEquals(Uuid::fromHexToBytes($id), $data[0]['id']);
        static::assertEmpty($data[0]['protected']);
    }

    public function testWriteWithPermission(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'systemProtected' => 'foobar',
        ];

        $this->getWriter()->insert($definition, [$data], $context);

        $data = $this->connection->fetchAllAssociative('SELECT * FROM `_test_nullable`');

        static::assertCount(1, $data);
        static::assertEquals(Uuid::fromHexToBytes($id), $data[0]['id']);
        static::assertEquals('foobar', $data[0]['system_protected']);
    }

    public function testWriteManyToOneWithoutPermission(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'relation' => [
                'id' => $id,
            ],
        ];

        $ex = null;

        try {
            $this->getWriter()->insert($definition, [$data], $context);
        } catch (WriteException $ex) {
        }

        static::assertInstanceOf(WriteException::class, $ex);
        static::assertCount(1, $ex->getExceptions());
        static::assertEquals('This field is write-protected.', $this->getValidationExceptionMessage($ex, 'relation'));

        $fieldException = $ex->getExceptions()[0];
        static::assertEquals(WriteConstraintViolationException::class, $fieldException::class);
        static::assertInstanceOf(WriteConstraintViolationException::class, $fieldException);
        static::assertEquals('/0/relation', $fieldException->getPath());
    }

    public function testWriteManyToOneWithPermission(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'systemRelation' => [
                'id' => $id,
            ],
        ];

        $this->getWriter()->insert($definition, [$data], $context);

        $data = $this->connection->fetchAllAssociative('SELECT * FROM `_test_nullable`');

        static::assertCount(1, $data);
        static::assertEquals(Uuid::fromHexToBytes($id), $data[0]['id']);
        static::assertEquals(Uuid::fromHexToBytes($id), $data[0]['system_relation_id']);
    }

    public function testWriteOneToManyWithoutPermission(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedRelationDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'wp' => [
                [
                    'id' => $id,
                ],
            ],
        ];

        $ex = null;

        try {
            $this->getWriter()->insert($definition, [$data], $context);
        } catch (WriteException $ex) {
        }

        static::assertInstanceOf(WriteException::class, $ex);
        static::assertCount(1, $ex->getExceptions());
        static::assertEquals('This field is write-protected.', $this->getValidationExceptionMessage($ex, 'wp'));

        $fieldException = $ex->getExceptions()[0];
        static::assertEquals(WriteConstraintViolationException::class, $fieldException::class);
        static::assertInstanceOf(WriteConstraintViolationException::class, $fieldException);
        static::assertEquals('/0/wp', $fieldException->getPath());
    }

    public function testWriteOneToManyWithPermission(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedRelationDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'systemWp' => [
                [
                    'systemProtected' => 'foobar',
                ],
            ],
        ];

        $this->getWriter()->insert($definition, [$data], $context);

        $data = $this->connection->fetchAllAssociative('SELECT * FROM `_test_nullable`');

        static::assertCount(1, $data);
    }

    public function testWriteManyToManyWithoutPermission(): void
    {
        $id = Uuid::randomHex();
        $id2 = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'relations' => [
                [
                    'id' => $id2,
                ],
            ],
        ];

        $ex = null;

        try {
            $this->getWriter()->insert($definition, [$data], $context);
        } catch (WriteException $ex) {
        }

        static::assertInstanceOf(WriteException::class, $ex);
        static::assertCount(1, $ex->getExceptions());
        static::assertEquals('This field is write-protected.', $this->getValidationExceptionMessage($ex, 'relations'));

        $fieldException = $ex->getExceptions()[0];
        static::assertEquals(WriteConstraintViolationException::class, $fieldException::class);
        static::assertInstanceOf(WriteConstraintViolationException::class, $fieldException);
        static::assertEquals('/0/relations', $fieldException->getPath());
    }

    public function testWriteManyToManyWithPermission(): void
    {
        $id = Uuid::randomHex();
        $id2 = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'systemRelations' => [
                [
                    'id' => $id2,
                ],
            ],
        ];

        $this->getWriter()->insert($definition, [$data], $context);

        $data = $this->connection->fetchAllAssociative('SELECT * FROM `_test_nullable_reference`');

        static::assertCount(1, $data);
        static::assertEquals(Uuid::fromHexToBytes($id), $data[0]['wp_id']);
        static::assertEquals(Uuid::fromHexToBytes($id2), $data[0]['relation_id']);
    }

    public function testWriteTranslationWithoutPermission(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedTranslatedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'protected' => 'foobar',
        ];

        $ex = null;

        try {
            $this->getWriter()->insert($definition, [$data], $context);
        } catch (WriteException $ex) {
        }

        static::assertInstanceOf(WriteException::class, $ex);
        static::assertCount(1, $ex->getExceptions());
        static::assertEquals('This field is write-protected.', $this->getValidationExceptionMessage($ex));

        $fieldException = $ex->getExceptions()[0];
        static::assertEquals(WriteConstraintViolationException::class, $fieldException::class);
        static::assertInstanceOf(WriteConstraintViolationException::class, $fieldException);
        static::assertEquals('/0/protected', $fieldException->getPath());
    }

    public function testWriteTranslationWithPermission(): void
    {
        $id = Uuid::randomHex();
        $context = $this->createWriteContext();
        $definition = static::getContainer()->get(WriteProtectedTranslatedDefinition::class);
        static::assertInstanceOf(EntityDefinition::class, $definition);

        $data = [
            'id' => $id,
            'systemProtected' => 'foobar',
        ];

        $this->getWriter()->insert($definition, [$data], $context);

        $data = $this->connection->fetchAllAssociative('SELECT * FROM `_test_nullable_translation`');

        static::assertCount(1, $data);
        static::assertEquals(Uuid::fromHexToBytes($id), $data[0]['_test_nullable_id']);
        static::assertEquals('foobar', $data[0]['system_protected']);
    }

    protected function createWriteContext(): WriteContext
    {
        return WriteContext::createFromContext(Context::createDefaultContext());
    }

    private function getWriter(): EntityWriterInterface
    {
        return static::getContainer()->get(EntityWriter::class);
    }

    private function getValidationExceptionMessage(WriteException $ex, string $field = 'protected'): string|\Stringable
    {
        $message = '';

        foreach ($ex->getExceptions() as $exception) {
            static::assertInstanceOf(\Throwable::class, $exception);
            $message = $exception->getMessage();

            if ($exception instanceof WriteConstraintViolationException && $exception->getPath() === '/0/' . $field) {
                return $exception->getViolations()->get(0)->getMessage();
            }
        }

        return $message;
    }
}
