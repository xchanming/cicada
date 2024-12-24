<?php

declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Command;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Command\StoreLoginCommand;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[Package('checkout')]
class StoreLoginCommandTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testEmptyPasswordOption(): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(StoreLoginCommand::class));

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('The password cannot be empty');

        $commandTester->setInputs(['', '', '']);
        $commandTester->execute([
            '--cicadaId' => 'no-reply@cicada.de',
            '--user' => 'missing_user',
        ]);
    }

    public function testValidPasswordOptionInvalidUserOption(): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(StoreLoginCommand::class));

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('User not found');

        $commandTester->setInputs(['non-empty-password']);
        $commandTester->execute([
            '--cicadaId' => 'no-reply@cicada.de',
            '--user' => 'missing_user',
        ]);
    }
}
