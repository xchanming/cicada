<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

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
class Migration1580218617RefactorShippingMethodPrice extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1580218617;
    }

    public function update(Connection $connection): void
    {
        $this->updateSchema($connection);
        $this->createUpdateTrigger($connection);
        $this->createInsertTrigger($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    public function updateSchema(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `shipping_method_price` MODIFY `currency_id` binary(16) NULL;
            ALTER TABLE `shipping_method_price` MODIFY `price` double NULL;
            ALTER TABLE `shipping_method_price` ADD COLUMN `currency_price` json NULL AFTER `price`;'
        );
    }

    private function createUpdateTrigger(Connection $connection): void
    {
        $query
            = 'CREATE TRIGGER shipping_method_price_new_price_update BEFORE UPDATE ON shipping_method_price
            FOR EACH ROW
            BEGIN
                IF @TRIGGER_DISABLED IS NULL OR @TRIGGER_DISABLED = 0 THEN
                IF (NEW.price != OLD.price OR (NEW.price IS NOT NULL AND OLD.price IS NULL))
                        OR (NEW.currency_id != OLD.currency_id OR (NEW.currency_id IS NOT NULL AND OLD.currency_id IS NULL))
                        AND (NEW.currency_price = OLD.currency_price OR (NEW.currency_price IS NULL AND OLD.currency_price IS NULL)) THEN
                    SET NEW.currency_price = JSON_OBJECT(
                        CONCAT("c", LOWER(HEX(NEW.currency_id))),
                        JSON_OBJECT(
                            "net", NEW.price,
                            "gross", NEW.price,
                            "linked", false,
                            "currencyId", LOWER(HEX(NEW.currency_id))
                        )
                    );
                ELSEIF (NEW.price = OLD.price OR NEW.price IS NULL)
                        AND (NEW.currency_id = OLD.currency_id OR NEW.currency_id IS NULL)
                        AND (NEW.currency_price != OLD.currency_price OR (OLD.currency_price IS NULL AND NEW.currency_price IS NOT NULL)) THEN
                    SET NEW.price = JSON_UNQUOTE(JSON_EXTRACT(
                        NEW.currency_price,
                        CONCAT("$.", JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(NEW.currency_price), "$[0]")), ".gross")
                    )) + 0.0;

                    SET NEW.currency_id = UNHEX(JSON_UNQUOTE(JSON_EXTRACT(
                        NEW.currency_price,
                        CONCAT("$.", JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(NEW.currency_price), "$[0]")), ".currencyId")
                    )));
                END IF;
                END IF;
            END;';

        $this->createTrigger($connection, $query);
    }

    private function createInsertTrigger(Connection $connection): void
    {
        $query
            = 'CREATE TRIGGER shipping_method_price_new_price_insert BEFORE INSERT ON shipping_method_price
            FOR EACH ROW
            BEGIN
                IF @TRIGGER_DISABLED IS NULL OR @TRIGGER_DISABLED = 0 THEN
                IF NEW.price IS NOT NULL AND NEW.currency_id IS NOT NULL AND NEW.currency_price IS NULL THEN
                    SET NEW.currency_price = JSON_OBJECT(
                        CONCAT("c", LOWER(HEX(NEW.currency_id))),
                        JSON_OBJECT(
                            "net", NEW.price,
                            "gross", NEW.price,
                            "linked", false,
                            "currencyId", LOWER(HEX(NEW.currency_id))
                        )
                    );
                ELSEIF NEW.price IS NULL AND NEW.currency_id IS NULL AND NEW.currency_price IS NOT NULL THEN
                    SET NEW.price = JSON_UNQUOTE(JSON_EXTRACT(
                        NEW.currency_price,
                        CONCAT("$.", JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(NEW.currency_price), "$[0]")), ".gross")
                    )) + 0.0;

                    SET NEW.currency_id = UNHEX(JSON_UNQUOTE(JSON_EXTRACT(
                        NEW.currency_price,
                        CONCAT("$.", JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(NEW.currency_price), "$[0]")), ".currencyId")
                    )));
                END IF;
                END IF;
            END;';

        $this->createTrigger($connection, $query);
    }
}
