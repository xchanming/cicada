<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\Controller;

use Cicada\Core\Framework\Adapter\Cache\CacheClearer;
use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Api\Controller\CacheController;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @internal
 */
#[CoversClass(CacheController::class)]
class CacheControllerTest extends TestCase
{
    public function testClearCache(): void
    {
        $cacheClearerMock = $this->createMock(CacheClearer::class);
        $cacheClearerMock->expects(static::once())
            ->method('clear');

        $controller = new CacheController(
            $cacheClearerMock,
            $this->createMock(CacheInvalidator::class),
            new NullAdapter(),
            $this->createMock(EntityIndexerRegistry::class),
        );

        $controller->clearCache();
    }

    public function testClearDelayedCache(): void
    {
        $cacheInvalidatorMock = $this->createMock(CacheInvalidator::class);
        $cacheInvalidatorMock->expects(static::once())
            ->method('invalidateExpired');

        $controller = new CacheController(
            $this->createMock(CacheClearer::class),
            $cacheInvalidatorMock,
            new NullAdapter(),
            $this->createMock(EntityIndexerRegistry::class),
        );

        $controller->clearDelayedCache();
    }
}
