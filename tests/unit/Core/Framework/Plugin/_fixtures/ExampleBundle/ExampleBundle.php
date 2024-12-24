<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Plugin\_fixtures\ExampleBundle;

use Cicada\Core\Framework\Parameter\AdditionalBundleParameters;
use Cicada\Core\Framework\Plugin;
use Cicada\Tests\Unit\Core\Framework\Plugin\_fixtures\ExampleBundle\FeatureA\FeatureA;
use Cicada\Tests\Unit\Core\Framework\Plugin\_fixtures\ExampleBundle\FeatureB\FeatureB;

/**
 * @internal
 */
class ExampleBundle extends Plugin
{
    public function getAdditionalBundles(AdditionalBundleParameters $parameters): array
    {
        return [
            new FeatureA(),
            new FeatureB(),
        ];
    }
}
