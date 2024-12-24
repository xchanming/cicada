<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Maintenance\SalesChannel\Command;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelMaintenanceEnableCommand;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[Package('core')]
class SalesChannelMaintenanceEnableCommandTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testNoValidationErrors(): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(SalesChannelMaintenanceEnableCommand::class));
        $commandTester->execute([]);

        static::assertSame(
            0,
            $commandTester->getStatusCode(),
            "\"bin/console sales-channel:maintenance:enable\" returned errors:\n" . $commandTester->getDisplay()
        );
    }

    public function testUnknownSalesChannelIds(): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(SalesChannelMaintenanceEnableCommand::class));
        $commandTester->execute(['ids' => [Uuid::randomHex()]]);

        static::assertSame(
            'No sales channels were updated',
            $commandTester->getDisplay()
        );
    }

    public function testNoSalesChannelIds(): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(SalesChannelMaintenanceEnableCommand::class));
        $commandTester->execute([]);

        static::assertSame(
            'No sales channels were updated. Provide id(s) or run with --all option.',
            $commandTester->getDisplay()
        );
    }

    public function testOneSalesChannelIds(): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(SalesChannelMaintenanceEnableCommand::class));
        $commandTester->execute(['ids' => [TestDefaults::SALES_CHANNEL]]);

        static::assertSame(
            'Updated maintenance mode for 1 sales channel(s)',
            $commandTester->getDisplay()
        );
    }

    public function testAllSalesChannelIds(): void
    {
        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');
        $count = $salesChannelRepository->search(new Criteria(), Context::createDefaultContext())->getTotal();

        $commandTester = new CommandTester(static::getContainer()->get(SalesChannelMaintenanceEnableCommand::class));
        $commandTester->execute(['--all' => true]);

        static::assertSame(
            \sprintf('Updated maintenance mode for %d sales channel(s)', $count),
            $commandTester->getDisplay()
        );
    }
}
