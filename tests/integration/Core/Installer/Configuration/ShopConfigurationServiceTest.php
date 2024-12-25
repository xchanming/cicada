<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Installer\Configuration;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Installer\Configuration\ShopConfigurationService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ShopConfigurationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testUpdateShop(): void
    {
        $service = new ShopConfigurationService();

        $connection = static::getContainer()->get(Connection::class);

        $service->updateShop([
            'name' => 'test-shop',
            'locale' => 'zh-CN',
            'currency' => 'USD',
            'additionalCurrencies' => ['EUR', 'CHF'],
            'country' => 'DEU',
            'email' => 'test@test.com',
            'host' => 'localhost',
            'schema' => 'https',
            'basePath' => '/shop',
            'blueGreenDeployment' => true,
        ], $connection);

        // assert that system language was updated
        static::assertSame('Deutsch', $connection->fetchOne('SELECT `name` from `language` WHERE `id` = ?', [Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM)]));
        // assert that default currency was updated
        static::assertSame('USD', $connection->fetchOne('SELECT `iso_code` from `currency` WHERE `id` = ?', [Uuid::fromHexToBytes(Defaults::CURRENCY)]));

        $currencies = $connection->fetchAllKeyValue('SELECT `id`, `iso_code` from `currency`');
        // assert that not configured currencies are deleted
        static::assertEqualsCanonicalizing(['USD', 'EUR', 'CHF'], array_values($currencies));

        // assert that sales channel was created
        $id = $connection->fetchOne('SELECT `sales_channel_id` FROM `sales_channel_translation` WHERE `name` = ?', ['test-shop']);
        static::assertIsString($id);

        $salesChannel = $connection->fetchAssociative('SELECT * FROM `sales_channel` WHERE `id` = ?', [$id]);
        static::assertIsArray($salesChannel);
        static::assertSame(Uuid::fromHexToBytes(Defaults::CURRENCY), $salesChannel['currency_id']);
        static::assertSame(Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM), $salesChannel['language_id']);

        static::assertSame('DE', $connection->fetchOne('SELECT `iso` FROM `country` WHERE `id` = ?', [$salesChannel['country_id']]));

        static::assertEqualsCanonicalizing(array_keys($currencies), $connection->fetchFirstColumn('SELECT `currency_id` FROM `sales_channel_currency` WHERE `sales_channel_id` = ?', [$id]));

        $domains = $connection->fetchAllAssociative('SELECT * FROM `sales_channel_domain` WHERE `sales_channel_id` = ?', [$id]);
        static::assertCount(2, $domains);

        foreach ($domains as $domain) {
            static::assertSame(Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM), $domain['language_id']);
            static::assertSame(Uuid::fromHexToBytes(Defaults::CURRENCY), $domain['currency_id']);
        }

        static::assertEqualsCanonicalizing([
            'https://localhost/shop',
            'http://localhost/shop',
        ], array_column($domains, 'url'));
    }
}
