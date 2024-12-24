<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Command;

use Cicada\Core\Framework\App\AppUrlChangeResolver\Resolver;
use Cicada\Core\Framework\App\Command\ResolveAppUrlChangeCommand;
use Cicada\Core\Framework\Context;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class ResolveAppUrlChangeCommandTest extends TestCase
{
    public function testResolveAppUrlChangeChoosesRightStrategy(): void
    {
        $urlChangeStrategy = $this->createMock(Resolver::class);
        $urlChangeStrategy->expects(static::once())
            ->method('getAvailableStrategies')
            ->willReturn([
                'testStrategy' => 'test Description',
                'secondStrategy' => 'second Description',
            ]);

        $urlChangeStrategy->expects(static::once())
            ->method('resolve')
            ->with(
                'testStrategy',
                static::isInstanceOf(Context::class)
            );

        $commandTester = new CommandTester(
            new ResolveAppUrlChangeCommand($urlChangeStrategy)
        );

        $commandTester->setInputs(['testStrategy']);
        $commandTester->execute([]);

        static::assertSame(0, $commandTester->getStatusCode());

        static::assertStringContainsString('Choose what strategy should be applied, to resolve the app url change?:', $commandTester->getDisplay());
        static::assertStringContainsString('testStrategy', $commandTester->getDisplay());
        static::assertStringContainsString('secondStrategy', $commandTester->getDisplay());
        static::assertStringContainsString('[OK] Strategy "testStrategy" was applied successfully', $commandTester->getDisplay());
    }

    public function testResolveAppUrlChangeWithProvidedStrategy(): void
    {
        $urlChangeStrategy = $this->createMock(Resolver::class);
        $urlChangeStrategy->expects(static::once())
            ->method('getAvailableStrategies')
            ->willReturn([
                'testStrategy' => 'test Description',
                'secondStrategy' => 'second Description',
            ]);

        $urlChangeStrategy->expects(static::once())
            ->method('resolve')
            ->with(
                'testStrategy',
                static::isInstanceOf(Context::class)
            );

        $commandTester = new CommandTester(
            new ResolveAppUrlChangeCommand($urlChangeStrategy)
        );

        $commandTester->execute(['strategy' => 'testStrategy']);

        static::assertSame(0, $commandTester->getStatusCode());

        static::assertStringNotContainsString('Choose what strategy should be applied, to resolve the app url change?:', $commandTester->getDisplay());
        static::assertStringContainsString('[OK] Strategy "testStrategy" was applied successfully', $commandTester->getDisplay());
    }

    public function testResolveAppUrlWithNotFoundStrategy(): void
    {
        $urlChangeStrategy = $this->createMock(Resolver::class);
        $urlChangeStrategy->expects(static::once())
            ->method('getAvailableStrategies')
            ->willReturn([
                'testStrategy' => 'test Description',
                'secondStrategy' => 'second Description',
            ]);

        $urlChangeStrategy->expects(static::once())
            ->method('resolve')
            ->with(
                'testStrategy',
                static::isInstanceOf(Context::class)
            );

        $commandTester = new CommandTester(
            new ResolveAppUrlChangeCommand($urlChangeStrategy)
        );

        $commandTester->setInputs(['testStrategy']);
        $commandTester->execute(['strategy' => 'doesNotExist']);

        static::assertSame(0, $commandTester->getStatusCode());

        static::assertStringContainsString('[NOTE] Strategy with name: "doesNotExist" not found.', $commandTester->getDisplay());
        static::assertStringContainsString('Choose what strategy should be applied, to resolve the app url change?:', $commandTester->getDisplay());
        static::assertStringContainsString('testStrategy', $commandTester->getDisplay());
        static::assertStringContainsString('secondStrategy', $commandTester->getDisplay());
        static::assertStringContainsString('[OK] Strategy "testStrategy" was applied successfully', $commandTester->getDisplay());
    }
}
