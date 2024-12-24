<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
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
class Migration1610439375AddEUStatesAsDefaultForIntraCommunityDeliveryLabel extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610439375;
    }

    public function update(Connection $connection): void
    {
        $this->addDeliveryCountriesIntoInvoiceDocumentConfig($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function addDeliveryCountriesIntoInvoiceDocumentConfig(Connection $connection): void
    {
        $listInvoiceData = $connection->fetchAllAssociative(
            'SELECT `document_base_config`.`id`, `document_base_config`.`config` FROM `document_base_config`
            LEFT JOIN `document_type` ON `document_base_config`.`document_type_id` = `document_type`.`id`
            WHERE `document_type`.`technical_name` = :documentName',
            ['documentName' => InvoiceRenderer::TYPE]
        );

        $euStates = $connection->fetchFirstColumn(
            'SELECT `id` FROM `country` WHERE `iso`
                IN (\'AT\', \'BE\', \'BG\', \'CY\', \'CZ\', \'DE\', \'DK\', \'EE\', \'GR\', \'ES\', \'FI\', \'FR\', \'GB\', \'HU\', \'IE\', \'IT\',
                \'LT\', \'LU\', \'LV\', \'MT\', \'NL\', \'PL\', \'PT\', \'RO\', \'SE\', \'SI\', \'SK\', \'HR\')'
        );

        foreach ($listInvoiceData as $invoiceData) {
            $invoiceConfig = json_decode($invoiceData['config'] ?? '[]', true, 512, \JSON_THROW_ON_ERROR);
            $invoiceConfig['deliveryCountries'] = Uuid::fromBytesToHexList($euStates);

            $connection->executeStatement(
                'UPDATE `document_base_config` SET `config` = :invoiceData WHERE `id` = :documentConfigId',
                [
                    'invoiceData' => json_encode($invoiceConfig, \JSON_THROW_ON_ERROR),
                    'documentConfigId' => $invoiceData['id'],
                ]
            );
        }
    }
}
