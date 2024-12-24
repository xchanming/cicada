<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Adapter\Cache\CacheClearer;
use Cicada\Core\Framework\Adapter\Command\CacheClearAllCommand;
use Cicada\Core\Framework\Adapter\Command\CacheClearHttpCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
#[CoversClass(CacheClearAllCommand::class)]
class CacheClearHttpCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $cache = $this->createMock(CacheClearer::class);
        $cache->expects(static::once())->method('clearHttpCache');

        $command = new CacheClearHttpCommand($cache);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}
