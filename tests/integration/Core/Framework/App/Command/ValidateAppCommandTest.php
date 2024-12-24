<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Command;

use Cicada\Core\Framework\App\Command\ValidateAppCommand;
use Cicada\Core\Framework\App\Validation\ManifestValidator;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class ValidateAppCommandTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testValidateApp(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->execute(['name' => 'withoutPermissions']);

        static::assertSame(0, $commandTester->getStatusCode());
        static::assertStringContainsString('[OK]', $commandTester->getDisplay());
    }

    public function testUsesAllAppFoldersFromAppDirIfMissingArgument(): void
    {
        $commandTester = new CommandTester($this->createCommand(__DIR__ . '/_fixtures'));
        $commandTester->execute([]);

        static::assertSame(1, $commandTester->getStatusCode());
        static::assertStringContainsString('[ERROR] The app "validationFailure" is invalid', $commandTester->getDisplay());
        static::assertStringContainsString('[ERROR] The app "validationFailures" is invalid', $commandTester->getDisplay());
    }

    private function createCommand(string $appFolder): ValidateAppCommand
    {
        return new ValidateAppCommand(
            $appFolder,
            static::getContainer()->get(ManifestValidator::class)
        );
    }
}
