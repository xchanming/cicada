<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Installer;

use Cicada\Core\Installer\Installer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[CoversClass(Installer::class)]
class InstallerTest extends TestCase
{
    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new FrameworkExtension());

        $installer = new Installer();
        $installer->build($container);

        static::assertSame(
            [
                'zh' => 'zh-CN',
                'en' => 'en-GB',
            ],
            $container->getParameter('cicada.installer.supportedLanguages')
        );
    }
}
