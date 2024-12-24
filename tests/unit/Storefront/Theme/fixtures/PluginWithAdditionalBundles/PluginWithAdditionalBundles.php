<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\fixtures\PluginWithAdditionalBundles;

use Cicada\Core\Framework\Parameter\AdditionalBundleParameters;
use Cicada\Core\Framework\Plugin;
use Cicada\Tests\Unit\Storefront\Theme\fixtures\PluginWithAdditionalBundles\SubBundle1\SubBundle1;

/**
 * @internal
 */
class PluginWithAdditionalBundles extends Plugin
{
    public function getAdditionalBundles(AdditionalBundleParameters $additionalBundleParameters): array
    {
        return [
            new SubBundle1(),
        ];
    }
}
