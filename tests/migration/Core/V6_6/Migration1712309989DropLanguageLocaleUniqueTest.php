<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Migration\V6_6\Migration1712309989DropLanguageLocaleUnique;
use Cicada\Core\System\Language\LanguageDefinition;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1712309989DropLanguageLocaleUnique::class)]
class Migration1712309989DropLanguageLocaleUniqueTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        Feature::skipTestIfInActive('v6.6.0.0', $this);

        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testGetCreationTimestamp(): void
    {
        static::assertSame(1712309989, (new Migration1712309989DropLanguageLocaleUnique())->getCreationTimestamp());
    }

    public function testMigrate(): void
    {
        $this->rollback();
        $this->migrate();

        $manager = $this->connection->createSchemaManager();
        $indexes = $manager->listTableIndexes(LanguageDefinition::ENTITY_NAME);

        static::assertArrayNotHasKey('uniq.translation_code_id', $indexes);
    }

    private function migrate(): void
    {
        (new Migration1712309989DropLanguageLocaleUnique())->update($this->connection);
    }

    private function rollback(): void
    {
        $this->connection->executeStatement('ALTER TABLE `language` ADD UNIQUE `uniq.translation_code_id` (translation_code_id)');
    }
}
