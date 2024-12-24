<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class CustomerIndexingMessage extends EntityIndexingMessage
{
    /**
     * @var string[]
     */
    private array $ids = [];

    /**
     * @return string[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * @param array<string> $ids
     */
    public function setIds(array $ids): void
    {
        $this->ids = $ids;
    }
}
