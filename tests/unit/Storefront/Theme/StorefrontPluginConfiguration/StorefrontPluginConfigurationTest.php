<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\StorefrontPluginConfiguration;

use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StorefrontPluginConfiguration::class)]
class StorefrontPluginConfigurationTest extends TestCase
{
    public function testAssetName(): void
    {
        $config = new StorefrontPluginConfiguration('SwagPayPal');
        static::assertEquals('swag-pay-pal', $config->getAssetName());
    }
}
