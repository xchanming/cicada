<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1620820321AddDefaultDomainForHeadlessSaleschannel extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1620820321;
    }

    public function update(Connection $connection): void
    {
        $headlessSalesChannels = $connection->fetchFirstColumn(
            'SELECT `id` FROM `sales_channel` WHERE `type_id` = :headlessType',
            ['headlessType' => Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_API)]
        );

        $snippetSetId = $connection->fetchOne('SELECT id from snippet_set WHERE iso = :iso', [
            'iso' => 'en-GB',
        ]);

        if ($snippetSetId === false) {
            return;
        }

        foreach ($headlessSalesChannels as $index => $headlessSalesChannelId) {
            $defaultDomainExist = $connection->fetchOne('SELECT id from sales_channel_domain WHERE sales_channel_id = :headlessSalesChannelId', [
                'headlessSalesChannelId' => $headlessSalesChannelId,
            ]);

            if ($defaultDomainExist) {
                continue;
            }

            $connection->insert(SalesChannelDomainDefinition::ENTITY_NAME, [
                'id' => Uuid::randomBytes(),
                'sales_channel_id' => $headlessSalesChannelId,
                'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                'currency_id' => Uuid::fromHexToBytes(Defaults::CURRENCY),
                'snippet_set_id' => $snippetSetId,
                'url' => 'default.headless' . $index,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
