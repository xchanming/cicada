<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme;

use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StorefrontPluginConfiguration::class)]
class StorefrontPluginConfigurationTest extends TestCase
{
    public function testAdditionalBundlesIsFalse(): void
    {
        $config = new StorefrontPluginConfiguration('name');

        static::assertFalse($config->hasAdditionalBundles());
    }

    public function testNameIsSet(): void
    {
        $config = new StorefrontPluginConfiguration('name');

        static::assertEquals('name', $config->getTechnicalName());
    }
}
