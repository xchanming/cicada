<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Asset;

use Cicada\Core\Framework\Adapter\Asset\FlysystemLastModifiedVersionStrategy;
use Cicada\Core\Framework\Adapter\Asset\PrefixVersionStrategy;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(PrefixVersionStrategy::class)]
class PrefixVersionStrategyTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testPathGetsPrefixed(string $prefix, string $fileName, string $returnPath, string $expected): void
    {
        $orgVersion = $this->createMock(FlysystemLastModifiedVersionStrategy::class);
        $orgVersion->method('applyVersion')->with(rtrim($prefix, '/') . '/' . ltrim($fileName, '/'))->willReturn($returnPath);

        $prefixVersion = new PrefixVersionStrategy(
            $prefix,
            $orgVersion
        );

        static::assertSame($expected, $prefixVersion->getVersion($fileName));
    }

    /**
     * @return iterable<string, array<int, string>>
     */
    public static function dataProvider(): iterable
    {
        yield 'One file' => [
            'prefix', // Prefix
            'test.txt', // File name
            'prefix/test.txt?123', // Return path
            'test.txt?123', // Expected end path
        ];

        yield 'Sub folder' => [
            'prefix', // Prefix
            'foo/test.txt', // File name
            'prefix/foo/test.txt?123', // Return path
            'foo/test.txt?123', // Expected end path
        ];

        yield 'Prefix contains slash' => [
            'prefix/', // Prefix
            'foo/test.txt', // File name
            'prefix/foo/test.txt?123', // Return path
            'foo/test.txt?123', // Expected end path
        ];

        yield 'Filename contains slash' => [
            'prefix', // Prefix
            '/foo/test.txt', // File name
            'prefix/foo/test.txt?123', // Return path
            '/foo/test.txt?123', // Expected end path
        ];
    }
}
