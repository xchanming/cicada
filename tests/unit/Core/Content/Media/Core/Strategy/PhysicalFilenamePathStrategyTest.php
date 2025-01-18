<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Core\Strategy;

use Cicada\Core\Content\Media\Core\Params\MediaLocationStruct;
use Cicada\Core\Content\Media\Core\Params\ThumbnailLocationStruct;
use Cicada\Core\Content\Media\Core\Strategy\PhysicalFilenamePathStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PhysicalFilenamePathStrategy::class)]
class PhysicalFilenamePathStrategyTest extends TestCase
{
    #[DataProvider('strategyProvider')]
    public function testStrategy(MediaLocationStruct|ThumbnailLocationStruct $struct, ?string $expected): void
    {
        $strategy = new PhysicalFilenamePathStrategy();

        $generate = $strategy->generate([$struct]);

        if ($expected === null) {
            static::assertArrayNotHasKey($struct->id, $generate);

            return;
        }

        static::assertArrayHasKey($struct->id, $generate);

        static::assertSame($expected, $generate[$struct->id]);
    }

    public static function strategyProvider(): \Generator
    {
        yield 'Test without extension' => [
            new MediaLocationStruct('foo', null, 'test', null),
            null,
        ];

        yield 'Test with extension' => [
            new MediaLocationStruct('foo', 'jpg', 'test', null),
            'media/09/8f/6b/test.jpg',
        ];

        yield 'Test with extension and cache buster' => [
            new MediaLocationStruct('foo', 'jpg', 'test', new \DateTimeImmutable('2021-01-01')),
            'media/1b/1d/92/1609430400/test.jpg',
        ];

        yield 'Test with thumbnail' => [
            new ThumbnailLocationStruct(
                'foo',
                100,
                100,
                new MediaLocationStruct('foo', 'jpg', 'test', new \DateTimeImmutable('2021-01-01'))
            ),
            'thumbnail/1b/1d/92/1609430400/test_100x100.jpg',
        ];
        yield 'Test file name results into /ad' => [
            new MediaLocationStruct('foo', 'jpg', '018b3c6d2ddf726fb12ee582f5caba40', new \DateTimeImmutable('2021-01-01')),
            'media/d2/62/a4/1609430400/018b3c6d2ddf726fb12ee582f5caba40.jpg',
        ];
    }
}
