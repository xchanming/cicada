<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Adapter\Cache\CacheClearer;
use Cicada\Core\Framework\Adapter\Command\CacheClearAllCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(CacheClearAllCommand::class)]
class CacheClearAllCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $cacheClearer = $this->createMock(CacheClearer::class);
        $cacheClearer->expects(static::once())->method('clear')->with(true);

        $command = new CacheClearAllCommand($cacheClearer, 'dev', true);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}
