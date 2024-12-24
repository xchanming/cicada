<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\Test;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\Command\DataAbstractionLayerValidateCommand;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class DataAbstractionLayerValidateCommandTest extends TestCase
{
    use KernelTestBehaviour;

    public function testNoValidationErrors(): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(DataAbstractionLayerValidateCommand::class));
        $commandTester->execute([]);

        static::assertEquals(
            0,
            $commandTester->getStatusCode(),
            "\"bin/console dal:validate\" returned errors:\n" . $commandTester->getDisplay()
        );
    }
}
