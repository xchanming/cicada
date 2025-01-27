<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme;

use Cicada\Storefront\Theme\CompilerConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CompilerConfiguration::class)]
class CompilerConfigurationTest extends TestCase
{
    public function testGetNotSetValue(): void
    {
        $config = new CompilerConfiguration([]);

        static::assertNull($config->getValue('test'));
    }

    public function testGetSetValue(): void
    {
        $config = new CompilerConfiguration([
            'test' => 'value',
        ]);

        static::assertEquals('value', $config->getValue('test'));
    }

    public function testGetWholeConfiguration(): void
    {
        $config = new CompilerConfiguration([
            'test' => 'value',
        ]);

        static::assertEquals([
            'test' => 'value',
        ], $config->getConfiguration());
    }
}
