<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
abstract class AbstractRuleLoader
{
    abstract public function getDecorated(): AbstractRuleLoader;

    abstract public function load(Context $context): RuleCollection;
}
