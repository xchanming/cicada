<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\CashPayment;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1697112043AddPaymentAndShippingTechnicalName extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1697112043;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: 'payment_method',
            column: 'technical_name',
            type: 'VARCHAR(255)'
        );

        if (!$this->indexExists($connection, PaymentMethodDefinition::ENTITY_NAME, 'uniq.technical_name')) {
            $connection->executeStatement('ALTER TABLE `payment_method` ADD CONSTRAINT `uniq.technical_name` UNIQUE (`technical_name`)');
        }

        $this->addColumn(
            connection: $connection,
            table: 'shipping_method',
            column: 'technical_name',
            type: 'VARCHAR(255)'
        );

        if (!$this->indexExists($connection, ShippingMethodDefinition::ENTITY_NAME, 'uniq.technical_name')) {
            $connection->executeStatement('ALTER TABLE `shipping_method` ADD CONSTRAINT `uniq.technical_name` UNIQUE (`technical_name`)');
        }

        // set technical name for existing payment methods
        // Cicada\Core\...\DebitPayment becomes payment_debitpayment
        // app payment methods will use 'payment_[appName_appPaymentMethodIdentifier]` as technical name
        $connection->executeStatement(
            '
                UPDATE IGNORE `payment_method`
                LEFT JOIN `app_payment_method` ON `app_payment_method`.`payment_method_id` = `payment_method`.`id`
                SET `payment_method`.`technical_name` = CONCAT(\'payment_\', LOWER(SUBSTRING_INDEX(`handler_identifier`, :slash, -1)))
                WHERE `payment_method`.`technical_name` IS NULL
                AND (`app_payment_method`.`identifier` IS NOT NULL OR `payment_method`.`handler_identifier` IN (:handlers))
            ',
            ['handlers' => [CashPayment::class, PrePayment::class], 'slash' => '\\'],
            ['handlers' => ArrayParameterType::STRING]
        );

        $this->updateShippingMethodName('普通物流', 'Standard', $connection);
        $this->updateShippingMethodName('快递物流', 'Express', $connection);
        $this->updateAppShippingMethods($connection);
    }

    private function updateShippingMethodName(string $name, string $technicalName, Connection $connection): void
    {
        $connection->executeStatement(
            '
            UPDATE IGNORE `shipping_method` SET `technical_name` = CONCAT(\'shipping_\', LOWER(:technicalName))
            WHERE `id` = (
                SELECT `shipping_method_id` FROM `shipping_method_translation`
                WHERE `language_id` = :languageId
                AND `name` = :name
                ORDER BY `created_at`
                LIMIT 1
            )
            AND `technical_name` IS NULL
            ',
            [
                'name' => $name,
                'technicalName' => $technicalName,
                'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            ]
        );
    }

    private function updateAppShippingMethods(Connection $connection): void
    {
        $connection->executeStatement(
            '
            UPDATE IGNORE `shipping_method`
            LEFT JOIN `app_shipping_method` ON `app_shipping_method`.`shipping_method_id` = `shipping_method`.`id`
            SET `shipping_method`.`technical_name` = CONCAT(\'shipping_\', `app_shipping_method`.`app_name`, \'_\', `app_shipping_method`.`identifier`)
            WHERE `shipping_method`.`technical_name` IS NULL;
            '
        );
    }
}
