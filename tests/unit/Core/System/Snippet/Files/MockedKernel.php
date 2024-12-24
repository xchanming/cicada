<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Snippet\Files;

use Cicada\Core\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @internal
 *
 * @method void configureContainer(ContainerBuilder $container, LoaderInterface $loader)
 */
class MockedKernel extends Kernel
{
    /**
     * @param array<string, BundleInterface> $bundles
     */
    public function __construct(array $bundles)
    {
        $this->bundles = $bundles;
    }
}
