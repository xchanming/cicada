<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Processor\_fixtures;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 *
 * @phpstan-ignore-next-line
 */
#[Package('checkout')]
class ContainerItem extends LineItem
{
    /**
     * @param array<LineItem> $items
     */
    public function __construct(array $items = [])
    {
        parent::__construct(Uuid::randomHex(), LineItem::CONTAINER_LINE_ITEM);

        $this->children = new LineItemCollection($items);

        $this->removable = true;
        $this->good = true;
    }
}
