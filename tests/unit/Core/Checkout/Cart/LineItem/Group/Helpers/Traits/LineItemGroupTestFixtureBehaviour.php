<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group\Helpers\Traits;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupDefinition;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @deprecated tag:v6.7.0 - reason:becomes-internal - Will be internal in v6.7.0
 */
#[Package('checkout')]
trait LineItemGroupTestFixtureBehaviour
{
    private function buildGroup(string $packagerKey, float $value, string $sorterKey, RuleCollection $rules): LineItemGroupDefinition
    {
        $group = new LineItemGroupDefinition(
            Uuid::randomBytes(),
            $packagerKey,
            $value,
            $sorterKey,
            $rules
        );

        return $group;
    }
}
