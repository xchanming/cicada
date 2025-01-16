<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Aware;

use Cicada\Core\Framework\Event\IsFlowEventAware;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
#[IsFlowEventAware]
interface OrderTransactionAware
{
    public const ORDER_TRANSACTION_ID = 'orderTransactionId';

    public const ORDER_TRANSACTION = 'orderTransaction';

    public function getOrderTransactionId(): string;
}
