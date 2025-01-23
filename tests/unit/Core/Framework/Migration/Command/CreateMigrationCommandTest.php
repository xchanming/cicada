<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Migration\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Migration\Command\CreateMigrationCommand;
use Cicada\Core\Framework\Migration\MigrationException;
use Cicada\Core\Framework\Plugin\KernelPluginCollection;
use Cicada\Tests\Integration\Storefront\Theme\fixtures\SimplePlugin\SimplePlugin as SimplePluginIntegration;
use Cicada\Tests\Unit\Storefront\Theme\fixtures\SimplePlugin\SimplePlugin;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(CreateMigrationCommand::class)]
class CreateMigrationCommandTest extends TestCase
{
    public function testExecuteThrowsExceptionIfNameContainsForbiddenCharacters(): void
    {
        $command = new CreateMigrationCommand(
            new KernelPluginCollection(),
            'coreDir',
            'shopwareVersion'
        );
        $commandTester = new CommandTester($command);

        $input = ['--name' => '%%%%'];

        $this->expectExceptionObject(MigrationException::invalidArgument('Migration name contains forbidden characters!'));

        $commandTester->execute($input);
    }

    public function testExecuteThrowsExceptionWhenDirectoryIsSpecifiedButNoNamespace(): void
    {
        $command = new CreateMigrationCommand(
            new KernelPluginCollection(),
            'coreDir',
            'shopwareVersion'
        );
        $commandTester = new CommandTester($command);

        $input = ['directory' => 'test-dir'];

        $this->expectExceptionObject(MigrationException::invalidArgument('Please specify both dir and namespace or none.'));

        $commandTester->execute($input);
    }

    public function testExecuteThrowsExceptionWhenPluginIsNotFound(): void
    {
        $command = new CreateMigrationCommand(
            new KernelPluginCollection(),
            'coreDir',
            'shopwareVersion'
        );
        $commandTester = new CommandTester($command);

        $input = ['--plugin' => 'test-plugin'];

        $this->expectExceptionObject(MigrationException::pluginNotFound('test-plugin'));

        $commandTester->execute($input);
    }

    public function testExecuteThrowsExceptionWhenMoreThanOnePluginIsFound(): void
    {
        $kernelPluginCollection = new KernelPluginCollection();
        $plugin1 = new SimplePlugin(true, '');
        $plugin2 = new SimplePluginIntegration(true, '');
        $kernelPluginCollection->addList([$plugin1, $plugin2]);

        $command = new CreateMigrationCommand(
            $kernelPluginCollection,
            'coreDir',
            'shopwareVersion'
        );
        $commandTester = new CommandTester($command);

        $input = ['--plugin' => 'SimplePlugin'];

        $this->expectExceptionObject(MigrationException::moreThanOnePluginFound(
            'SimplePlugin',
            array_keys($kernelPluginCollection->all())
        ));

        $commandTester->execute($input);
    }

    public function testExecuteThrowsExceptionWhenMigrationDirectoryNotCreated(): void
    {
        $kernelPluginCollection = new KernelPluginCollection();
        $kernelPluginCollection->add(new SimplePlugin(true, ''));

        $command = new CreateMigrationCommand(
            $kernelPluginCollection,
            'coreDir',
            'shopwareVersion'
        );
        $commandTester = new CommandTester($command);

        $input = ['--plugin' => 'SimplePlugin'];

        $this->expectExceptionObject(MigrationException::migrationDirectoryNotCreated(
            '/tests/unit/Storefront/Theme/fixtures/SimplePlugin/Migration'
        ));

        $commandTester->execute($input);
    }
}
