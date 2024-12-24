<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Event;

use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
abstract class DocumentOrderEvent extends Event
{
    /**
     * @param DocumentGenerateOperation[] $operations
     */
    public function __construct(
        private readonly OrderCollection $orders,
        private readonly Context $context,
        private readonly array $operations = []
    ) {
    }

    /**
     * @return DocumentGenerateOperation[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getOrders(): OrderCollection
    {
        return $this->orders;
    }
}
