<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Twig;

use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @internal
 */
class DisabledTwigCacheWarmupTest extends TestCase
{
    use KernelTestBehaviour;

    public function testServiceIsRemoved(): void
    {
        static::expectException(ServiceNotFoundException::class);
        static::getContainer()->get('twig.template_cache_warmer');
    }
}
