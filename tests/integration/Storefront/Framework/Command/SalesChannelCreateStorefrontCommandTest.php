<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Command;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Storefront\Framework\Command\SalesChannelCreateStorefrontCommand;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[Package('buyers-experience')]
class SalesChannelCreateStorefrontCommandTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
    }

    #[DataProvider('dataProviderTestExecuteCommandSuccess')]
    public function testExecuteCommandSuccessfully(string $isoCode, string $isoCodeExpected): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(SalesChannelCreateStorefrontCommand::class));
        $url = 'http://localhost/' . Uuid::randomHex();

        $commandTester->execute([
            '--name' => 'Storefront',
            '--url' => $url,
            '--isoCode' => $isoCode,
        ]);

        $saleChannelId = $commandTester->getInput()->getOption('id');

        $countSaleChannelId = $this->connection->fetchOne('SELECT COUNT(id) FROM sales_channel WHERE id = :id', ['id' => Uuid::fromHexToBytes($saleChannelId)]);

        static::assertEquals(1, $countSaleChannelId);

        $getIsoCodeSql = <<<'SQL'
            SELECT snippet_set.iso
            FROM sales_channel_domain
            JOIN snippet_set ON snippet_set.id = sales_channel_domain.snippet_set_id
            WHERE sales_channel_id = :saleChannelId
        SQL;
        $isoCodeResult = $this->connection->fetchOne($getIsoCodeSql, ['saleChannelId' => Uuid::fromHexToBytes($saleChannelId)]);

        static::assertEquals($isoCodeExpected, $isoCodeResult);

        $commandTester->assertCommandIsSuccessful();
    }

    public static function dataProviderTestExecuteCommandSuccess(): \Generator
    {
        yield 'should success with valid iso code' => [
            'isoCode' => 'de_DE',
            'isoCodeExpected' => 'en-GB',
        ];

        yield 'should success with invalid iso code' => [
            'isoCode' => 'xy-XY',
            'isoCodeExpected' => 'en-GB',
        ];
    }
}
