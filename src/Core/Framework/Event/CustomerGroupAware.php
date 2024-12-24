<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
#[IsFlowEventAware]
interface CustomerGroupAware
{
    public const CUSTOMER_GROUP_ID = 'customerGroupId';

    public const CUSTOMER_GROUP = 'customerGroup';

    public function getCustomerGroupId(): string;
}
