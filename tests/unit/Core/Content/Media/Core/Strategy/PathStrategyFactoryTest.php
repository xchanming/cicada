<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Core\Strategy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Media\Core\Strategy\FilenamePathStrategy;
use Cicada\Core\Content\Media\Core\Strategy\IdPathStrategy;
use Cicada\Core\Content\Media\Core\Strategy\PathStrategyFactory;
use Cicada\Core\Content\Media\Core\Strategy\PhysicalFilenamePathStrategy;
use Cicada\Core\Content\Media\Core\Strategy\PlainPathStrategy;
use Cicada\Core\Content\Media\MediaException;

/**
 * @internal
 */
#[CoversClass(PathStrategyFactory::class)]
#[CoversClass(IdPathStrategy::class)]
#[CoversClass(FilenamePathStrategy::class)]
#[CoversClass(PhysicalFilenamePathStrategy::class)]
#[CoversClass(PlainPathStrategy::class)]
class PathStrategyFactoryTest extends TestCase
{
    public function testRegistry(): void
    {
        $registry = new PathStrategyFactory([
            new IdPathStrategy(),
            new PhysicalFilenamePathStrategy(),
            new PlainPathStrategy(),
            new FilenamePathStrategy(),
        ]);

        static::assertInstanceOf(FilenamePathStrategy::class, $registry->factory('filename'));
        static::assertInstanceOf(PhysicalFilenamePathStrategy::class, $registry->factory('physical_filename'));
        static::assertInstanceOf(PlainPathStrategy::class, $registry->factory('plain'));
        static::assertInstanceOf(IdPathStrategy::class, $registry->factory('id'));

        static::expectException(MediaException::class);

        $factory = $registry->factory('invalid');
    }
}
