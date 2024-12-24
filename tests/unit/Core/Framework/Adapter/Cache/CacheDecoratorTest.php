<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Adapter\Cache\CacheDecorator;
use Cicada\Core\Framework\Adapter\Cache\CacheTagCollection;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(CacheDecorator::class)]
class CacheDecoratorTest extends TestCase
{
    public function testResetIsPassedToDecoration(): void
    {
        $adapter = $this->createMock(TagAwareAdapter::class);
        $adapter
            ->expects(static::once())
            ->method('reset');

        $decorator = new CacheDecorator($adapter, new CacheTagCollection());
        $decorator->reset();
    }
}
