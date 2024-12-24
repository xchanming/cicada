<?php declare(strict_types=1);

namespace Cicada\Core\System\Tag\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('inventory')]
class FilteredTagIdsStruct extends Struct
{
    /**
     * @param array<string> $ids
     */
    public function __construct(
        protected array $ids,
        protected int $total
    ) {
    }

    /**
     * @return array<string>
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
