<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Transaction\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<Transaction>
 */
#[Package('checkout')]
class TransactionCollection extends Collection
{
    public function getApiAlias(): string
    {
        return 'cart_transaction_collection';
    }

    protected function getExpectedClass(): ?string
    {
        return Transaction::class;
    }
}
