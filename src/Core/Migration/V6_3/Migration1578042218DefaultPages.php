<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

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
#[Package('framework')]
class Migration1578042218DefaultPages extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1578042218;
    }

    public function update(Connection $connection): void
    {
        $this->fixContactPageAssignment($connection);

        if ($connection->fetchOne('SELECT COUNT(*) FROM cms_page WHERE locked = 0')) {
            // User has already created pages
            return;
        }

        $pages = [
            [
                'key' => 'core.basicInformation.shippingPaymentInfoPage',
                'zh' => '支付 & 物流',
                'en' => 'Payment / Shipping',
            ],
            [
                'key' => 'core.basicInformation.tosPage',
                'zh' => '服务条款',
                'en' => 'Terms of service',
            ],
            [
                'key' => 'core.basicInformation.privacyPage',
                'zh' => '隐私协议',
                'en' => 'Privacy',
            ],
        ];

        foreach ($pages as $page) {
            $id = $this->createEmptyPage($page['en'], $page['zh'], $connection);

            $connection->insert('system_config', [
                'id' => Uuid::randomBytes(),
                'configuration_key' => $page['key'],
                'configuration_value' => \sprintf('{"_value": "%s"}', Uuid::fromBytesToHex($id)),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function createEmptyPage(string $titleEn, string $titleDe, Connection $connection): string
    {
        $id = Uuid::randomBytes();
        $sectionId = Uuid::randomBytes();
        $blockId = Uuid::randomBytes();
        $slotId = Uuid::randomBytes();
        $versionId = $connection->fetchOne('SELECT version_id FROM cms_slot LIMIT 1');
        $languageIdDefault = $this->getLanguageIdByLocale($connection, 'en-GB');
        $languageIdDe = $this->getLanguageIdByLocale($connection, 'zh-CN');

        $connection->insert('cms_page', [
            'id' => $id,
            'type' => 'page',
            'entity' => null,
            'preview_media_id' => null,
            'locked' => 0,
            'config' => null,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        if ($languageIdDefault !== $languageIdDe) {
            $connection->insert('cms_page_translation', [
                'cms_page_id' => $id,
                'language_id' => $languageIdDefault,
                'name' => $titleEn,
                'custom_fields' => null,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        if ($languageIdDe) {
            $connection->insert('cms_page_translation', [
                'cms_page_id' => $id,
                'language_id' => $languageIdDe,
                'name' => $titleDe,
                'custom_fields' => null,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        $connection->insert('cms_section', [
            'id' => $sectionId,
            'cms_page_id' => $id,
            'position' => 0,
            'type' => 'default',
            'name' => null,
            'locked' => 0,
            'sizing_mode' => 'boxed',
            'mobile_behavior' => 'wrap',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('cms_block', [
            'id' => $blockId,
            'cms_section_id' => $sectionId,
            'position' => 0,
            'section_position' => 'main',
            'type' => 'text',
            'locked' => 0,
            'margin_top' => '20px',
            'margin_bottom' => '20px',
            'margin_left' => '20px',
            'margin_right' => '20px',
            'background_media_mode' => 'cover',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('cms_slot', [
            'id' => $slotId,
            'version_id' => $versionId,
            'cms_block_id' => $blockId,
            'type' => 'text',
            'slot' => 'content',
            'locked' => 0,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $contentEn = [
            'content' => [
                'value' => \sprintf('<h2>%s</h2><hr><p>%s</p>', $titleEn, 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'),
                'source' => 'static',
            ],
            'verticalAlign' => [
                'value' => null,
                'source' => 'static',
            ],
        ];

        $contentDe = [
            'content' => [
                'value' => \sprintf('<h2>%s</h2><hr><p>%s</p>', $titleDe, 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'),
                'source' => 'static',
            ],
            'verticalAlign' => [
                'value' => null,
                'source' => 'static',
            ],
        ];

        if ($languageIdDefault !== $languageIdDe) {
            $connection->insert('cms_slot_translation', [
                'cms_slot_id' => $slotId,
                'cms_slot_version_id' => $versionId,
                'language_id' => $languageIdDefault,
                'config' => json_encode($contentEn),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        if ($languageIdDe) {
            $connection->insert('cms_slot_translation', [
                'cms_slot_id' => $slotId,
                'cms_slot_version_id' => $versionId,
                'language_id' => $languageIdDe,
                'config' => json_encode($contentDe),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        return $id;
    }

    private function fixContactPageAssignment(Connection $connection): void
    {
        if ($connection->fetchOne('SELECT 1 FROM system_config WHERE configuration_key = ?', ['core.basicInformation.contactPage'])) {
            // Contact page is assigned
            return;
        }

        $id = $connection->fetchOne('SELECT cms_page_id FROM cms_page_translation WHERE name = ?', ['Default shop page layout with contact form']);
        if ($id) {
            $connection->insert('system_config', [
                'id' => Uuid::randomBytes(),
                'configuration_key' => 'core.basicInformation.contactPage',
                'configuration_value' => \sprintf('{"_value": "%s"}', Uuid::fromBytesToHex($id)),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
    }

    private function getLanguageIdByLocale(Connection $connection, string $locale): ?string
    {
        $sql = <<<'SQL'
SELECT `language`.`id`
FROM `language`
INNER JOIN `locale` ON `locale`.`id` = `language`.`locale_id`
WHERE `locale`.`code` = :code
SQL;

        /** @var string|false $languageId */
        $languageId = $connection->executeQuery($sql, ['code' => $locale])->fetchOne();
        if (!$languageId && $locale !== 'en-GB') {
            return null;
        }

        if (!$languageId) {
            return Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        }

        return $languageId;
    }
}
