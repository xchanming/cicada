<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\Filter;

use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('core')]
class EqualsAnyFilter extends SingleFieldFilter
{
    /**
     * @param list<string>|array<string, string>|list<float>|list<int> $value
     */
    public function __construct(
        protected readonly string $field,
        protected array $value = []
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return list<string>|array<string, string>|list<float>|list<int>
     */
    public function getValue(): array
    {
        return $this->value;
    }

    public function getFields(): array
    {
        return [$this->field];
    }
}
