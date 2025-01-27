<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Maintenance\Staging\Handler;

use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Maintenance\Staging\Event\SetupStagingEvent;
use Cicada\Core\Maintenance\Staging\Handler\StagingAppHandler;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[CoversClass(StagingAppHandler::class)]
class StagingAppHandlerTest extends TestCase
{
    public function testDeletion(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->willReturn([
                ['id' => 'app_id', 'integration_id' => 'integration_id', 'name' => 'test'],
            ]);

        $tables = [];
        $ids = [];

        $connection
            ->method('delete')
            ->willReturnCallback(function (string $table, array $criteria) use (&$tables, &$ids): void {
                $tables[] = $table;
                $ids[] = $criteria['id'];
            });

        $configService = new StaticSystemConfigService();
        $configService->set(ShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY, 'test');

        $handler = new StagingAppHandler($connection, $configService);
        $handler->__invoke(new SetupStagingEvent(Context::createDefaultContext(), $this->createMock(SymfonyStyle::class)));

        static::assertNull($configService->get(ShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY));

        static::assertSame(['app', 'integration'], $tables);
        static::assertSame(['app_id', 'integration_id'], $ids);
    }
}
