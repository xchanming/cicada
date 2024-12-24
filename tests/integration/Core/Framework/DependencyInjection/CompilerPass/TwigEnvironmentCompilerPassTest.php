<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DependencyInjection\CompilerPass;

use Cicada\Core\Framework\Adapter\Twig\TwigEnvironment;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\TwigEnvironmentCompilerPass;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TwigEnvironmentCompilerPass::class)]
class TwigEnvironmentCompilerPassTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testTwigServicesUsesOurImplementation(): void
    {
        static::assertInstanceOf(TwigEnvironment::class, static::getContainer()->get('twig'));

        static::assertSame(
            static::getContainer()->getParameter('kernel.cache_dir') . '/twig',
            static::getContainer()->getParameter('twig.cache')
        );
    }
}
