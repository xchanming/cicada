<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1602822727AddVatHandlingIntoCountryTable extends MigrationStep
{
    /**
     * @var array<string, string>
     */
    private array $countryIds;

    public function getCreationTimestamp(): int
    {
        return 1602822727;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `country`
            ADD COLUMN `company_tax_free` TINYINT (1) NOT NULL DEFAULT 0 AFTER `force_state_in_registration`,
            ADD COLUMN `check_vat_id_pattern` TINYINT (1) NOT NULL DEFAULT 0 AFTER `company_tax_free`,
            ADD COLUMN `vat_id_pattern` VARCHAR (255) NULL AFTER `check_vat_id_pattern`;
        ');

        $this->addCountryVatPattern($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function addCountryVatPattern(Connection $connection): void
    {
        $this->fetchCountryIds($connection);

        foreach ($this->getCountryVatPattern() as $isoCode => $countryVatPattern) {
            if (!\array_key_exists($isoCode, $this->countryIds)) {
                // country was deleted by shop owner
                continue;
            }

            $connection->update('country', ['vat_id_pattern' => $countryVatPattern], ['id' => $this->countryIds[$isoCode]]);
        }
    }

    private function fetchCountryIds(Connection $connection): void
    {
        /** @var list<array{id: string, iso: string}> $countries */
        $countries = $connection->executeQuery('SELECT `id`, `iso` FROM `country`')->fetchAllAssociative();

        foreach ($countries as $country) {
            $this->countryIds[$country['iso']] = $country['id'];
        }
    }

    /**
     * @return array<string, string>
     */
    private function getCountryVatPattern(): array
    {
        return [
            'CN' => '^[0-9A-Z]{18}$',
        ];
    }
}
