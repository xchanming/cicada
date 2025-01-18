<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\Traits\ImportTranslationsTrait;
use Cicada\Core\Migration\Traits\Translations;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Tests\Migration\MigrationTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ImportTranslationsTrait::class)]
class ImportTranslationTraitTest extends TestCase
{
    use ImportTranslationsTrait;
    use MigrationTestTrait;

    public function testChineseDefault(): void
    {
        $ids = new IdsCollection();

        $this->createLanguages($ids);

        $data = [
            'id' => Uuid::fromHexToBytes($ids->create('category')),
            'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'type' => 'category',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];

        KernelLifecycleManager::getConnection()
            ->insert('category', $data);

        $this->importTranslation(
            'category_translation',
            new Translations(
                [
                    'category_id' => Uuid::fromHexToBytes($ids->get('category')),
                    'category_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'name' => 'zh name',
                ],
                [
                    'category_id' => Uuid::fromHexToBytes($ids->get('category')),
                    'category_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'name' => 'en name',
                ]
            ),
            KernelLifecycleManager::getConnection()
        );

        $translations = KernelLifecycleManager::getConnection()
            ->fetchAllAssociative(
                'SELECT LOWER(HEX(language_id)) as array_key, category_translation.*  FROM category_translation WHERE category_id = :id',
                ['id' => Uuid::fromHexToBytes($ids->get('category'))]
            );

        $translations = FetchModeHelper::groupUnique($translations);

        static::assertArrayHasKey(Defaults::LANGUAGE_SYSTEM, $translations);
        static::assertArrayHasKey($ids->get('english'), $translations);
        static::assertArrayHasKey($ids->get('zh-2'), $translations);

        static::assertEquals('zh name', $translations[Defaults::LANGUAGE_SYSTEM]['name']);
        static::assertEquals('zh name', $translations[$ids->get('zh-2')]['name']);
        static::assertEquals('en name', $translations[$ids->get('english')]['name']);
    }

    private function createLanguages(IdsCollection $ids): void
    {
        $localeData = [
            [
                'id' => Uuid::fromHexToBytes($ids->create('firstLocale')),
                'code' => 'te-te',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            [
                'id' => Uuid::fromHexToBytes($ids->create('secondLocale')),
                'code' => 'fr-te',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
        ];

        $languageData = [
            [
                'id' => Uuid::fromHexToBytes($ids->create('english')),
                'name' => 'test',
                'locale_id' => $this->getLocaleId('en-GB'),
                'translation_code_id' => Uuid::fromHexToBytes($ids->get('firstLocale')),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            [
                'id' => Uuid::fromHexToBytes($ids->create('zh-2')),
                'name' => 'test',
                'locale_id' => $this->getLocaleId('zh-CN'),
                'translation_code_id' => Uuid::fromHexToBytes($ids->get('secondLocale')),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
        ];

        $connection = KernelLifecycleManager::getConnection();
        $connection->insert('locale', $localeData[0]);
        $connection->insert('locale', $localeData[1]);

        $connection->insert('language', $languageData[0]);
        $connection->insert('language', $languageData[1]);
    }

    private function getLocaleId(string $code): string
    {
        return KernelLifecycleManager::getConnection()
            ->fetchOne('SELECT id FROM locale WHERE code = :code', ['code' => $code]);
    }
}
