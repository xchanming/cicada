<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\SingleFieldFilter;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
class JoinGroup extends Filter
{
    /**
     * @param list<SingleFieldFilter> $queries
     */
    public function __construct(
        private readonly array $queries,
        private readonly string $path,
        private readonly string $suffix,
        private string $operator
    ) {
    }

    public function getFields(): array
    {
        $fields = [];
        foreach ($this->queries as $query) {
            foreach ($query->getFields() as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * @return list<SingleFieldFilter>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
}
