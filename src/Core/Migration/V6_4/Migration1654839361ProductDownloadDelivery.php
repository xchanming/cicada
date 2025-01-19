<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\Traits\ImportTranslationsTrait;
use Cicada\Core\Migration\Traits\Translations;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1654839361ProductDownloadDelivery extends MigrationStep
{
    use ImportTranslationsTrait;

    final public const DELIVERY_TIME_NAME_ZH = '自动交付';
    final public const DELIVERY_TIME_NAME_EN = 'Instant download';

    public function getCreationTimestamp(): int
    {
        return 1654839361;
    }

    public function update(Connection $connection): void
    {
        $downloadDeliveryTime = Uuid::randomBytes();

        $deliveryTimeTranslation = $connection->fetchOne(
            'SELECT LOWER(HEX(delivery_time_id)) FROM delivery_time_translation WHERE name = :deliveryTimeName',
            ['deliveryTimeName' => self::DELIVERY_TIME_NAME_ZH]
        );

        if ($deliveryTimeTranslation) {
            return;
        }

        $connection->insert('delivery_time', [
            'id' => $downloadDeliveryTime,
            'min' => 0,
            'max' => 0,
            'unit' => 'hour',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $translation = new Translations(
            [
                'delivery_time_id' => $downloadDeliveryTime,
                'name' => self::DELIVERY_TIME_NAME_EN,
            ],
            [
                'delivery_time_id' => $downloadDeliveryTime,
                'name' => self::DELIVERY_TIME_NAME_ZH,
            ]
        );

        $this->importTranslation('delivery_time_translation', $translation, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
