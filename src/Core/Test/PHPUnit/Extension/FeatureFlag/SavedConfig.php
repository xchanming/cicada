<?php declare(strict_types=1);

namespace Cicada\Core\Test\PHPUnit\Extension\FeatureFlag;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @phpstan-import-type FeatureFlagConfig from Feature
 */
#[Package('core')]
class SavedConfig
{
    /**
     * @var array<string, FeatureFlagConfig>|null
     */
    public ?array $savedFeatureConfig = null;

    /**
     * @var array<string, mixed>
     */
    public array $savedServerVars = [];
}
