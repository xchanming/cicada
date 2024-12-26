<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Command;

use Cicada\Core\Framework\App\Command\AppPrinter;
use Cicada\Core\Framework\App\Command\InstallAppCommand;
use Cicada\Core\Framework\App\Lifecycle\AppLifecycle;
use Cicada\Core\Framework\App\Lifecycle\AppLoader;
use Cicada\Core\Framework\App\Validation\ManifestValidator;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class InstallAppCommandTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $appRepository;

    protected function setUp(): void
    {
        $this->appRepository = static::getContainer()->get('app.repository');
    }

    public function testInstallWithoutPermissions(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->setInputs(['yes']);

        $commandTester->execute(['name' => 'withoutPermissions']);

        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        static::assertStringContainsString('[OK] App withoutPermissions has been successfully installed.', $commandTester->getDisplay());
    }

    public function testInstallWithForce(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));

        $commandTester->execute(['name' => 'withPermissions', '-f' => true]);

        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        static::assertStringContainsString('[OK] App withPermissions has been successfully installed.', $commandTester->getDisplay());
    }

    public function testInstallWithPermissionsAndDomains(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->setInputs(['yes', 'yes']);

        $commandTester->execute(['name' => 'withPermissions']);

        static::assertSame(0, $commandTester->getStatusCode());
        $display = $commandTester->getDisplay();

        // header permissions
        static::assertMatchesRegularExpression('/.*Resource\s+Privileges\s+\n.*/', $display);
        // content permissions
        static::assertMatchesRegularExpression('/.*product\s+write, delete\s+\n.*/', $display);
        static::assertMatchesRegularExpression('/.*category\s+write\s+\n.*/', $display);
        static::assertMatchesRegularExpression('/.*order\s+read\s+\n.*/', $display);
        static::assertMatchesRegularExpression('/.*user_change_me\s+\n.*/', $display);

        // header domains
        static::assertMatchesRegularExpression('/.*Domain\s+\n.*/', $display);
        // content domains
        static::assertMatchesRegularExpression('/.*my.app.com\s+\n.*/', $display);
        static::assertMatchesRegularExpression('/.*swag-test.com\s+\n.*/', $display);

        static::assertStringContainsString('[OK] App withPermissions has been successfully installed.', $display);
    }

    public function testInstallWithAllowedHosts(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->setInputs(['yes', 'yes']);

        $commandTester->execute(['name' => 'withAllowedHosts']);

        static::assertSame(0, $commandTester->getStatusCode());
        $display = $commandTester->getDisplay();

        // header domain
        static::assertMatchesRegularExpression('/.*Domain\s+\n.*/', $display);
        // content domains
        static::assertMatchesRegularExpression('/.*xchanming.com\s+\n.*/', $display);
        static::assertMatchesRegularExpression('/.*example.com\s+\n.*/', $display);

        static::assertStringContainsString('[OK] App withAllowedHosts has been successfully installed.', $display);
    }

    public function testInstallWithPermissionsCancel(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->setInputs(['no']);

        $commandTester->execute(['name' => 'withPermissions']);

        static::assertSame(1, $commandTester->getStatusCode());
        $display = $commandTester->getDisplay();

        // header permissions
        static::assertMatchesRegularExpression('/.*Resource\s+Privileges\s+\n.*/', $display);
        // content permissions
        static::assertMatchesRegularExpression('/.*product\s+write, delete\s+\n.*/', $display);
        static::assertMatchesRegularExpression('/.*category\s+write\s+\n.*/', $display);
        static::assertMatchesRegularExpression('/.*order\s+read\s+\n.*/', $display);
        static::assertMatchesRegularExpression('/.*user_change_me\s+\n.*/', $display);

        static::assertStringContainsString('Aborting due to user input.', $commandTester->getDisplay());
    }

    public function testInstallWithActivation(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->setInputs(['yes']);

        $commandTester->execute(['name' => 'withoutPermissions', '-a' => true]);

        static::assertSame(0, $commandTester->getStatusCode());

        static::assertStringContainsString('[OK] App withoutPermissions has been successfully installed.', $commandTester->getDisplay());
    }

    public function testInstallWithNotFoundApp(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));

        $commandTester->execute(['name' => 'Test']);

        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        static::assertStringContainsString('[INFO] Could not find any app with this name', $commandTester->getDisplay());
    }

    public function testInstallFailsIfAppIsAlreadyInstalled(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->setInputs(['yes']);

        $commandTester->execute(['name' => 'withoutPermissions']);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $commandTester->execute(['name' => 'withoutPermissions']);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        static::assertStringContainsString('[INFO] App withoutPermissions is already installed', $commandTester->getDisplay());
    }

    public function testInstallFailsIfAppHasValidations(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/../Manifest/_fixtures'));
        $commandTester->setInputs(['yes', 'yes']);
        $commandTester->execute(['name' => 'invalidWebhooks']);

        static::assertSame(1, $commandTester->getStatusCode());
        static::assertStringContainsString('App installation of invalidWebhooks failed due: ', $commandTester->getDisplay());
    }

    public function testInstallInvalidAppWithNoValidate(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/../Manifest/_fixtures'));
        $commandTester->setInputs(['yes', 'yes']);
        $commandTester->execute(['name' => 'invalidWebhooks', '--no-validate' => true]);

        static::assertSame(0, $commandTester->getStatusCode());
        static::assertStringContainsString('App invalidWebhooks has been successfully installed.', $commandTester->getDisplay());
    }

    public function testInstallMultipleAppsAtOnceForced(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->setInputs(['yes']);

        $commandTester->execute(['name' => ['withoutPermissions', 'withPermissions'], '-a' => true, '-f' => true]);

        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        static::assertStringContainsString('[OK] App withoutPermissions has been successfully installed.', $commandTester->getDisplay());
        static::assertStringContainsString('[OK] App withPermissions has been successfully installed.', $commandTester->getDisplay());
    }

    private function createCommand(string $appFolder): InstallAppCommand
    {
        return new InstallAppCommand(
            new AppLoader($appFolder, new NullLogger()),
            static::getContainer()->get(AppLifecycle::class),
            new AppPrinter($this->appRepository),
            static::getContainer()->get(ManifestValidator::class)
        );
    }
}
