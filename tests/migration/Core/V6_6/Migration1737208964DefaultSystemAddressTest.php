<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Migration\V6_6\Migration1737208964DefaultSystemAddress;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(Migration1737208964DefaultSystemAddress::class)]
class Migration1737208964DefaultSystemAddressTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
        $this->connection->delete('system_config', ['configuration_key' => 'core.basicInformation.defaultAddress']);
    }

    public function testGetCreationTimestamp(): void
    {
        $migration = new Migration1737208964DefaultSystemAddress();
        static::assertSame(1737208964, $migration->getCreationTimestamp());
    }

    public function testMigration(): void
    {
        static::assertEmpty($this->getConfig());
        $migration = new Migration1737208964DefaultSystemAddress();
        $migration->update($this->connection);
        $record = $this->getConfig();
        static::assertArrayHasKey('configuration_key', $record);
        static::assertArrayHasKey('configuration_value', $record);

        $address = json_decode($record['configuration_value'], true, 512, \JSON_THROW_ON_ERROR)['_value'];

        $cityId = $this->connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM country_state
                      WHERE country_id = :countryId and parent_id=:stateId and short_code=:shortCode',
            ['countryId' => $address['countryId'], 'shortCode' => '5101', 'stateId' => $address['stateId']]
        );
        static::assertSame($cityId, $address['cityId']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        return $this->connection->fetchAssociative(
            'SELECT * FROM system_config WHERE configuration_key = \'core.basicInformation.defaultAddress\''
        ) ?: [];
    }
}
