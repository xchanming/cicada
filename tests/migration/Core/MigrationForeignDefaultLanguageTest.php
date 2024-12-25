<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationCollection;
use Cicada\Core\Framework\Migration\MigrationCollectionLoader;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\Traits\MigrationUntouchedDbTestTrait;
use Cicada\Core\Migration\V6_3\Migration1536233560BasicData;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[Group('slow')]
#[CoversClass(MigrationCollection::class)]
class MigrationForeignDefaultLanguageTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use MigrationUntouchedDbTestTrait;

    /**
     * No en-GB as language, de-LI as Default language and zh-CN as second language
     * All en-GB contents should be written in de-LI and de-De contents will be written in zh-CN
     */
    public function testMigrationWithoutEnGb(): void
    {
        $orgConnection = static::getContainer()->get(Connection::class);
        $orgConnection->rollBack();

        $connection = $this->setupDB($orgConnection);

        $migrationCollection = $this->collectMigrations();

        foreach ($migrationCollection->getMigrationSteps() as $_className => $migration) {
            try {
                $migration->update($connection);
            } catch (\Exception $e) {
                static::fail($_className . \PHP_EOL . $e->getMessage());
            }

            if ($this->isBasicDataMigration($_className)) {
                $deLiLocale = $connection->fetchAssociative(
                    'SELECT * FROM `locale` WHERE `code` = :code',
                    [
                        'code' => 'de-LI',
                    ]
                );
                static::assertIsArray($deLiLocale);

                $connection->update(
                    'language',
                    [
                        'name' => 'ForeignLang',
                        'locale_id' => $deLiLocale['id'],
                        'translation_code_id' => $deLiLocale['id'],
                    ],
                    ['id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]
                );
            }
        }
        foreach ($migrationCollection->getMigrationSteps() as $_className => $migration) {
            try {
                $migration->updateDestructive($connection);
            } catch (\Exception $e) {
                static::fail($_className . \PHP_EOL . $e->getMessage());
            }
        }

        $templateDefault = $connection->fetchAssociative(
            'SELECT subject FROM mail_template_translation
                WHERE subject = :subject AND language_id = :languageId',
            [
                'subject' => 'Password recovery',
                'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            ]
        );
        static::assertIsArray($templateDefault);
        static::assertEquals('Password recovery', $templateDefault['subject']);

        $deDeLanguage = $connection->fetchAssociative(
            'SELECT * FROM `language` WHERE `name` = :name',
            [
                'name' => 'Deutsch',
            ]
        );
        static::assertIsArray($deDeLanguage);

        $templateDeDe = $connection->fetchAssociative(
            'SELECT subject FROM mail_template_translation
                WHERE subject = :subject AND language_id = :languageId',
            [
                'subject' => 'Password-Wiederherstellung',
                'languageId' => $deDeLanguage['id'],
            ]
        );

        static::assertIsArray($templateDeDe);
        static::assertEquals('Password-Wiederherstellung', $templateDeDe['subject']);

        $orgConnection->beginTransaction();
    }

    /**
     * No En-GB and no zh-CN as language, de-LI as Default language and de-LU as second language
     * All en-GV contents should be written in de-LI and zh-CN contents will not be written
     * de-LI will be left empty
     */
    public function testMigrationWithoutEnGbOrDe(): void
    {
        $orgConnection = static::getContainer()->get(Connection::class);
        $orgConnection->rollBack();

        $connection = $this->setupDB($orgConnection);

        $migrationCollection = $this->collectMigrations();

        $deLuLanguage = [];

        foreach ($migrationCollection->getMigrationSteps() as $_className => $migration) {
            try {
                $migration->update($connection);
            } catch (\Exception $e) {
                static::fail($_className . \PHP_EOL . $e->getMessage());
            }

            if ($this->isBasicDataMigration($_className)) {
                $deLiLocale = $connection->fetchAssociative(
                    'SELECT * FROM `locale` WHERE `code` = :code',
                    [
                        'code' => 'de-LI',
                    ]
                );
                static::assertIsArray($deLiLocale);
                $connection->update(
                    'language',
                    [
                        'name' => 'ForeignLang',
                        'locale_id' => $deLiLocale['id'],
                        'translation_code_id' => $deLiLocale['id'],
                    ],
                    ['id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]
                );
                $deLuLocale = $connection->fetchAssociative(
                    'SELECT * FROM `locale` WHERE `code` = :code',
                    [
                        'code' => 'de-LU',
                    ]
                );
                static::assertIsArray($deLuLocale);

                $deLuLanguage = $connection->fetchAssociative(
                    'SELECT * FROM `language` WHERE `name` = :name',
                    [
                        'name' => 'Deutsch',
                    ]
                );
                static::assertIsArray($deLuLanguage);

                $connection->update(
                    'language',
                    [
                        'name' => 'OtherForeignLang',
                        'locale_id' => $deLuLocale['id'],
                        'translation_code_id' => $deLuLocale['id'],
                    ],
                    ['name' => 'Deutsch']
                );
            }
        }

        foreach ($migrationCollection->getMigrationSteps() as $_className => $migration) {
            try {
                $migration->updateDestructive($connection);
            } catch (\Exception $e) {
                static::fail($_className . \PHP_EOL . $e->getMessage());
            }
        }

        $templateDefault = $connection->fetchAssociative(
            'SELECT subject FROM mail_template_translation
                WHERE subject = :subject AND language_id = :languageId',
            [
                'subject' => 'Password recovery',
                'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            ]
        );
        static::assertIsArray($templateDefault);
        static::assertEquals('Password recovery', $templateDefault['subject']);

        $templateDeLu = $connection->fetchAssociative(
            'SELECT subject FROM mail_template_translation
                WHERE subject = :subject AND language_id = :languageId',
            [
                'subject' => 'Password recovery',
                'languageId' => $deLuLanguage['id'],
            ]
        );
        static::assertFalse($templateDeLu);

        $orgConnection->beginTransaction();
    }

    /**
     * En-GB and zh-CN as language, but de-LI as Default language
     * All en-GB contents should be written in En-GB and de-LI and zh-CN should be filled with zh-CN contents
     */
    public function testMigrationWithEnGbAndDeButDifferentDefault(): void
    {
        $orgConnection = static::getContainer()->get(Connection::class);
        $orgConnection->rollBack();

        $connection = $this->setupDB($orgConnection);

        $migrationCollection = $this->collectMigrations();
        $enGbId = Uuid::randomBytes();

        foreach ($migrationCollection->getMigrationSteps() as $_className => $migration) {
            try {
                $migration->update($connection);
            } catch (\Exception $e) {
                static::fail($_className . \PHP_EOL . $e->getMessage());
            }

            if ($this->isBasicDataMigration($_className)) {
                $deLiLocale = $connection->fetchAssociative(
                    'SELECT * FROM `locale` WHERE `code` = :code',
                    [
                        'code' => 'de-LI',
                    ]
                );
                static::assertIsArray($deLiLocale);
                $connection->update(
                    'language',
                    [
                        'name' => 'ForeignLang',
                        'locale_id' => $deLiLocale['id'],
                        'translation_code_id' => $deLiLocale['id'],
                    ],
                    ['id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]
                );
                $enGbLocale = $connection->fetchAssociative(
                    'SELECT * FROM `locale` WHERE `code` = :code',
                    [
                        'code' => 'en-GB',
                    ]
                );
                static::assertIsArray($enGbLocale);

                $connection->insert(
                    'language',
                    [
                        'id' => $enGbId,
                        'name' => 'English',
                        'locale_id' => $enGbLocale['id'],
                        'translation_code_id' => $enGbLocale['id'],
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
        }

        foreach ($migrationCollection->getMigrationSteps() as $_className => $migration) {
            try {
                $migration->updateDestructive($connection);
            } catch (\Exception $e) {
                static::fail($_className . \PHP_EOL . $e->getMessage());
            }
        }

        $templateDefault = $connection->fetchAssociative(
            'SELECT subject FROM mail_template_translation
                WHERE subject = :subject AND language_id = :languageId',
            [
                'subject' => 'Password recovery',
                'languageId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            ]
        );
        static::assertIsArray($templateDefault);
        static::assertEquals('Password recovery', $templateDefault['subject']);

        $templateEnGb = $connection->fetchAssociative(
            'SELECT subject FROM mail_template_translation
                WHERE subject = :subject AND language_id = :languageId',
            [
                'subject' => 'Password recovery',
                'languageId' => $enGbId,
            ]
        );
        static::assertIsArray($templateEnGb);
        static::assertEquals('Password recovery', $templateEnGb['subject']);

        $orgConnection->beginTransaction();
    }

    private function isBasicDataMigration(string $className): bool
    {
        return $className === Migration1536233560BasicData::class;
    }

    private function collectMigrations(): MigrationCollection
    {
        return static::getContainer()
            ->get(MigrationCollectionLoader::class)
            ->collectAllForVersion(
                static::getContainer()->getParameter('kernel.cicada_version'),
                MigrationCollectionLoader::VERSION_SELECTION_ALL
            );
    }

    private function setupDB(Connection $orgConnection): Connection
    {
        // Be sure that we are on the no migrations db
        static::assertStringContainsString('_no_migrations', $this->databaseName, 'Wrong DB ' . $this->databaseName);

        $orgConnection->executeStatement('DROP DATABASE IF EXISTS `' . $this->databaseName . '`');

        $orgConnection->executeStatement('CREATE DATABASE `' . $this->databaseName . '` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci');

        $connection = new Connection(
            array_merge(
                $orgConnection->getParams(),
                [
                    'url' => $_SERVER['DATABASE_URL'],
                    'dbname' => $this->databaseName,
                ]
            ),
            $orgConnection->getDriver(),
            $orgConnection->getConfiguration(),
        );

        /** @var string $dumpFile */
        $dumpFile = file_get_contents(__DIR__ . '/../../../src/Core/schema.sql');

        $connection->executeStatement($dumpFile);

        return $connection;
    }
}
