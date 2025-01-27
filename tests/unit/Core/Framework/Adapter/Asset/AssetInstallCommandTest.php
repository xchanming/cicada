<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Asset;

use Cicada\Core\Framework\Adapter\Asset\AssetInstallCommand;
use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\Plugin\Util\AssetService;
use Cicada\Core\Installer\Installer;
use Cicada\Tests\Unit\Core\Framework\Plugin\_fixtures\ExampleBundle\ExampleBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
#[CoversClass(AssetInstallCommand::class)]
class AssetInstallCommandTest extends TestCase
{
    public function testHtaccessCopy(): void
    {
        $fs = new Filesystem();
        $tmpDir = sys_get_temp_dir() . '/' . uniqid('cicada', true);
        $fs->mkdir($tmpDir . '/public');
        $fs->dumpFile($tmpDir . '/public/.htaccess.dist', 'FOO');

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($tmpDir);

        $command = new AssetInstallCommand(
            $kernel,
            $this->createMock(AssetService::class),
            $this->createMock(ActiveAppsLoader::class)
        );

        $runner = new CommandTester($command);
        $status = $runner->execute([]);

        static::assertSame(Command::SUCCESS, $status);
        static::assertFileExists($tmpDir . '/public/.htaccess');
        static::assertFileEquals($tmpDir . '/public/.htaccess.dist', $tmpDir . '/public/.htaccess');

        $fs->remove($tmpDir);
    }

    public function testForceOptionIsForwardedToService(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getBundles')->willReturn([$this->getBundle()]);

        $service = $this->createMock(AssetService::class);
        $appLoader = $this->createMock(ActiveAppsLoader::class);
        $appLoader->method('getActiveApps')->willReturn([]);

        $service->expects(static::once())
            ->method('copyAssetsFromBundle')
            ->with('ExampleBundle', true);

        $service->expects(static::once())
            ->method('copyAssets')
            ->with(static::isInstanceOf(Installer::class), true);

        $command = new AssetInstallCommand(
            $kernel,
            $service,
            $appLoader
        );

        $runner = new CommandTester($command);
        $runner->execute(['--force' => true]);
    }

    private function getBundle(): ExampleBundle
    {
        return new ExampleBundle(true, __DIR__ . '/_fixtures/ExampleBundle');
    }
}
