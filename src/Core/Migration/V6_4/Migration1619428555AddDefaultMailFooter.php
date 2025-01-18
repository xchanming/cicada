<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Content\MailTemplate\Aggregate\MailHeaderFooter\MailHeaderFooterDefinition;
use Cicada\Core\Content\MailTemplate\Aggregate\MailHeaderFooterTranslation\MailHeaderFooterTranslationDefinition;
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
class Migration1619428555AddDefaultMailFooter extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1619428555;
    }

    public function update(Connection $connection): void
    {
        $id = Uuid::randomBytes();

        $connection->insert(MailHeaderFooterDefinition::ENTITY_NAME, [
            'id' => $id,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'system_default' => 1,
        ]);

        $translations = new Translations(
            [
                'mail_header_footer_id' => $id,
                'name' => '默认邮件页脚',
                'description' => '默认邮件底部基本信息',
                'header_html' => null,
                'header_plain' => null,
                'footer_plain' => (string) \file_get_contents(__DIR__ . '/../Fixtures/mails/defaultMailFooter/zh-plain.twig'),
                'footer_html' => (string) \file_get_contents(__DIR__ . '/../Fixtures/mails/defaultMailFooter/zh-html.twig'),
            ],
            [
                'mail_header_footer_id' => $id,
                'name' => 'Default email footer',
                'description' => 'Default email footer derived from basic information',
                'header_html' => null,
                'header_plain' => null,
                'footer_plain' => (string) \file_get_contents(__DIR__ . '/../Fixtures/mails/defaultMailFooter/en-plain.twig'),
                'footer_html' => (string) \file_get_contents(__DIR__ . '/../Fixtures/mails/defaultMailFooter/en-html.twig'),
            ]
        );

        $this->importTranslation(MailHeaderFooterTranslationDefinition::ENTITY_NAME, $translations, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
