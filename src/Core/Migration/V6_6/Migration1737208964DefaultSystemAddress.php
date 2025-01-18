<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1737208964DefaultSystemAddress extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1737208964;
    }

    public function update(Connection $connection): void
    {
        $countryId = $connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM country WHERE iso3 = :iso3',
            ['iso3' => 'CHN']
        );

        $stateId = $connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM country_state WHERE country_id = :countryId and short_code=:shortCode',
            ['countryId' => $countryId, 'shortCode' => '51']
        );

        $cityId = $connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM country_state
                      WHERE country_id = :countryId and parent_id=:stateId and short_code=:shortCode',
            ['countryId' => $countryId, 'shortCode' => '5101', 'stateId' => $stateId]
        );

        $districtId = $connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM country_state
                      WHERE country_id = :countryId and parent_id=:stateId and short_code=:shortCode',
            ['countryId' => $countryId, 'shortCode' => '510156', 'stateId' => $cityId]
        );

        $address = [
            'countryId' => $countryId,
            'stateId' => $stateId,
            'cityId' => $cityId,
            'districtId' => $districtId,
        ];

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => 'core.basicInformation.defaultAddress',
            'configuration_value' => json_encode(['_value' => $address], \JSON_THROW_ON_ERROR),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }
}
