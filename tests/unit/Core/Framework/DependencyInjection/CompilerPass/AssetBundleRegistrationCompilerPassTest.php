<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DependencyInjection\CompilerPass;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\AssetBundleRegistrationCompilerPass;
use Cicada\Core\Framework\Framework;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Asset\Exception\InvalidArgumentException;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
#[CoversClass(AssetBundleRegistrationCompilerPass::class)]
class AssetBundleRegistrationCompilerPassTest extends TestCase
{
    public function testCompilerPass(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', [
            Framework::class,
            FrameworkBundle::class,
        ]);

        $service = new Definition(Packages::class);
        $service->setPublic(true);
        $container->setDefinition('assets.packages', $service);

        $container->setDefinition('cicada.asset.asset_without_versioning', new Definition(Package::class));
        $container->setDefinition('cicada.asset.asset.version_strategy', new Definition(EmptyVersionStrategy::class));

        $compilerPass = new AssetBundleRegistrationCompilerPass();

        $container->addCompilerPass($compilerPass);
        $compilerPass->process($container);

        $container->set('cicada.asset.asset_without_versioning', $this->createMock(Package::class));

        $assetService = $container->get('assets.packages');

        $assetService->getPackage('@Framework');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no "@FrameworkBundle" asset package.');
        $assetService->getPackage('@FrameworkBundle');
    }
}
