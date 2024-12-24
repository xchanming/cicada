<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\V6_6\Migration1727768690UpdateDefaultEnglishPlainMailFooter;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(Migration1727768690UpdateDefaultEnglishPlainMailFooter::class)]
class Migration1727768690UpdateDefaultEnglishPlainMailFooterTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testMigrationOfUnmodifiedTranslation(): void
    {
        $defaultLanguageId = $this->fetchDefaultLanguageId();

        $migration = new Migration1727768690UpdateDefaultEnglishPlainMailFooter();
        $migration->update($this->connection);
        $migration->update($this->connection);

        $systemDefaultMailHeaderFooterId = $this->connection->fetchOne('SELECT `id` FROM `mail_header_footer` WHERE `system_default` = 1');
        $mailHeaderFooterTranslation = $this->fetchMailHeaderFooterTranslation($systemDefaultMailHeaderFooterId, $defaultLanguageId);

        static::assertNotFalse($mailHeaderFooterTranslation);
        static::assertArrayHasKey('footer_plain', $mailHeaderFooterTranslation);
        static::assertSame($this->getExpectedMailPlainEnFooter(), $mailHeaderFooterTranslation['footer_plain']);
    }

    public function testMigrationOfWithModifiedTranslation(): void
    {
        $defaultLanguageId = $this->fetchDefaultLanguageId();
        $systemDefaultMailHeaderFooterId = $this->connection->fetchOne('SELECT `id` FROM `mail_header_footer` WHERE `system_default` = 1');

        $changedEnPlainFooter = 'hello world';
        $this->updatePlainFooterTranslation($systemDefaultMailHeaderFooterId, $defaultLanguageId, $changedEnPlainFooter);

        $migration = new Migration1727768690UpdateDefaultEnglishPlainMailFooter();
        $migration->update($this->connection);
        $migration->update($this->connection);

        $mailHeaderFooterTranslation = $this->fetchMailHeaderFooterTranslation($systemDefaultMailHeaderFooterId, $defaultLanguageId);

        static::assertNotFalse($mailHeaderFooterTranslation);
        static::assertArrayHasKey('footer_plain', $mailHeaderFooterTranslation);
        static::assertSame($changedEnPlainFooter, $mailHeaderFooterTranslation['footer_plain']);
    }

    private function fetchDefaultLanguageId(): string
    {
        $code = 'en-GB';
        $langId = $this->connection->fetchOne('
        SELECT `language`.`id` FROM `language` INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id` WHERE `code` = :code LIMIT 1
        ', ['code' => $code]);

        if (!$langId) {
            return Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        }

        return $langId;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchMailHeaderFooterTranslation(string $mailHeaderFooterId, string $languageId): array|false
    {
        return $this->connection->fetchAssociative('SELECT * FROM `mail_header_footer_translation` WHERE `mail_header_footer_id`= :mailHeaderFooterId AND `language_id` = :enLangId', [
            'mailHeaderFooterId' => $mailHeaderFooterId,
            'enLangId' => $languageId,
        ]);
    }

    private function getExpectedMailPlainEnFooter(): string
    {
        return '

        Address:
        {{ config(\'core.basicInformation.address\')|striptags(\'<br>\')|replace({"<br>":"\n"}) }}

        Bank account:
        {{ config(\'core.basicInformation.bankAccount\')|striptags(\'<br>\')|replace({"<br>":"\n"}) }}
';
    }

    private function updatePlainFooterTranslation(mixed $systemDefaultMailHeaderFooterId, ?string $defaultLanguageId, string $enPlainFooter): void
    {
        $this->connection->executeStatement('UPDATE `mail_header_footer_translation` SET `footer_plain` = :footerPlain, `updated_at` = NOW() WHERE `mail_header_footer_id`= :mailHeaderFooterId AND `language_id` = :enLangId', [
            'footerPlain' => $enPlainFooter,
            'mailHeaderFooterId' => $systemDefaultMailHeaderFooterId,
            'enLangId' => $defaultLanguageId,
        ]);
    }
}
