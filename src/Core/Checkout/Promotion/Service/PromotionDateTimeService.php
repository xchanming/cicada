<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Service;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class PromotionDateTimeService implements PromotionDateTimeServiceInterface
{
    /**
     * function returns the actual date time as string
     * in format: Y-m-d H:i:s
     *
     * @throws \Exception
     */
    public function getNow(): string
    {
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('UTC'));

        return $now->format(Defaults::STORAGE_DATE_FORMAT);
    }
}
