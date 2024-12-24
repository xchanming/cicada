<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Installer;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use Cicada\Core\Installer\Installer;
use Cicada\Core\Installer\InstallerKernel;
use Cicada\Core\TestBootstrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

/**
 * @internal
 */
#[CoversClass(InstallerKernel::class)]
class InstallerKernelTest extends TestCase
{
    use EnvTestBehaviour;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setEnvVars(['COMPOSER_HOME' => null]);
    }

    public function testItCorrectlyConfiguresTheContainer(): void
    {
        $kernel = new InstallerKernel('test', false);
        $kernel->boot();
        static::assertTrue($kernel->getContainer()->hasParameter('kernel.cicada_version'));

        // the default revision changes per commit, if it is set we expect that it is correct
        static::assertTrue($kernel->getContainer()->hasParameter('kernel.cicada_version_revision'));

        static::assertEquals(
            [
                'FrameworkBundle' => FrameworkBundle::class,
                'TwigBundle' => TwigBundle::class,
                'Installer' => Installer::class,
            ],
            $kernel->getContainer()->getParameter('kernel.bundles')
        );
    }

    public function testItCorrectlyConfiguresProjectDir(): void
    {
        $kernel = new InstallerKernel('test', false);
        $kernel->boot();
        $projectDir = (new TestBootstrapper())->getProjectDir();

        static::assertSame($projectDir, $kernel->getContainer()->getParameter('kernel.project_dir'));
        static::assertSame($projectDir . '/var/cache/composer', EnvironmentHelper::getVariable('COMPOSER_HOME'));
    }
}
