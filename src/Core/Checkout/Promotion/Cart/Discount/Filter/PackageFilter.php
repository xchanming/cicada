<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Cart\Discount\Filter;

use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
abstract class PackageFilter
{
    abstract public function getDecorated(): PackageFilter;

    abstract public function filterPackages(DiscountLineItem $discount, DiscountPackageCollection $packages, int $originalPackageCount): DiscountPackageCollection;
}
