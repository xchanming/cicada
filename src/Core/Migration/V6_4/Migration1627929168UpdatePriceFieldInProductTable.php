<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1627929168UpdatePriceFieldInProductTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1627929168;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'UPDATE product
             SET price = JSON_SET(
                price,
                "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.percentage",
                JSON_OBJECT(
                    "net",
                    COALESCE(
                        ROUND(
                            (
                                IF(
                                    IFNULL(JSON_UNQUOTE(JSON_EXTRACT(price, "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.listPrice.net")), 0) = 0, 0,
                                    100 - JSON_UNQUOTE(JSON_EXTRACT(price, CONCAT("$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.net"))) /
                                    JSON_UNQUOTE(JSON_EXTRACT(price, "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.listPrice.net")) * 100
                                )
                            ),
                            2
                        ),
                        0
                    ),
                    "gross",
                    COALESCE(
                        ROUND(
                                (
                                    IF (
                                        IFNULL(JSON_UNQUOTE(JSON_EXTRACT(price, "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.listPrice.gross")), 0) = 0,
                                        0,
                                        100 - JSON_UNQUOTE(JSON_EXTRACT(price, CONCAT("$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.gross"))) /
                                        JSON_UNQUOTE(JSON_EXTRACT(price, "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.listPrice.gross")) * 100
                                    )
                                )
                            ,2
                        ),
                    0)
                )
             )
             WHERE JSON_UNQUOTE(JSON_EXTRACT(price, "$.cb7d2554b0ce847cd82f3ac9bd1c0dfca.listPrice")) IS NOT NULL'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
