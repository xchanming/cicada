<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Installer\Requirements;

use Cicada\Core\Installer\Requirements\IniConfigReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IniConfigReader::class)]
class IniConfigReaderTest extends TestCase
{
    #[DataProvider('configProvider')]
    public function testGet(string $key, string|false $configValue, string $expectedValue): void
    {
        \ini_set($key, (string) $configValue);

        $reader = new IniConfigReader();
        static::assertSame($expectedValue, $reader->get($key));

        \ini_restore($key);
    }

    public static function configProvider(): \Generator
    {
        yield 'max_execution_time' => [
            'max_execution_time',
            '30',
            '30',
        ];

        yield 'memory_limit' => [
            'memory_limit',
            '512M',
            '512M',
        ];

        yield 'not set value' => [
            'max_execution_time',
            false,
            '',
        ];
    }
}
