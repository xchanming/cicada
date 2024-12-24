<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Cache\InvalidatorStorage\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Adapter\Cache\CacheClearer;
use Cicada\Core\Framework\Adapter\Cache\Message\CleanupOldCacheFolders;
use Cicada\Core\Framework\Adapter\Cache\Message\CleanupOldCacheFoldersHandler;

/**
 * @internal
 */
#[CoversClass(CleanupOldCacheFoldersHandler::class)]
class CleanupOldCacheFoldersHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $cacheClearer = $this->createMock(CacheClearer::class);
        $cacheClearer->expects(static::once())->method('cleanupOldContainerCacheDirectories');

        $handler = new CleanupOldCacheFoldersHandler($cacheClearer);
        $handler(new CleanupOldCacheFolders());
    }
}
