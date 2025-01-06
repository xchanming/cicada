<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Service;

use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
interface PromotionDateTimeServiceInterface
{
    public function getNow(): string;
}
