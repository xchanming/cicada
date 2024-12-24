<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Doctrine\Doctrine;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class MultiInsertQueryQueueTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testNullableDatetime(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $query = new MultiInsertQueryQueue($connection);

        $date = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $catA = Uuid::randomBytes();
        $catB = Uuid::randomBytes();

        $query->addInsert(
            'category',
            [
                'id' => $catA,
                'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'type' => CategoryDefinition::TYPE_LINK,
                'created_at' => $date,
                'updated_at' => null,
            ]
        );
        $query->addInsert(
            'category',
            [
                'id' => $catB,
                'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'type' => CategoryDefinition::TYPE_LINK,
                'created_at' => $date,
                'updated_at' => $date,
            ]
        );

        $query->execute();

        $actualA = $connection->fetchOne('SELECT updated_at FROM `category` WHERE id = :id', ['id' => $catA]);

        static::assertNotFalse($actualA);
        static::assertNull($actualA);

        $actualB = $connection->fetchOne('SELECT updated_at FROM `category` WHERE id = :id', ['id' => $catB]);

        static::assertNotFalse($actualB);
        static::assertSame($date, $actualB);
    }

    public function testAddUpdateFieldOnDuplicateKey(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $query = new MultiInsertQueryQueue($connection);

        $date = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $categoryId = Uuid::randomBytes();

        $query->addInsert(
            'category',
            [
                'id' => $categoryId,
                'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'type' => CategoryDefinition::TYPE_LINK,
                'created_at' => $date,
            ]
        );
        $query->addInsert(
            'category',
            [
                'id' => $categoryId,
                'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'type' => CategoryDefinition::TYPE_FOLDER,
                'created_at' => $date,
            ]
        );

        $query->addUpdateFieldOnDuplicateKey('category', 'type');
        $query->execute();

        $type = $connection->fetchOne('SELECT type FROM `category` WHERE id = :id', ['id' => $categoryId]);

        static::assertNotFalse($type);
        static::assertSame(CategoryDefinition::TYPE_FOLDER, $type);
    }
}
