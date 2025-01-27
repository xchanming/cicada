<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\Query;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('framework')]
class ScoreQuery extends Filter
{
    public function __construct(
        protected Filter $query,
        protected float $score,
        protected ?string $scoreField = null
    ) {
    }

    public function getFields(): array
    {
        return $this->query->getFields();
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getQuery(): Filter
    {
        return $this->query;
    }

    public function getScoreField(): ?string
    {
        return $this->scoreField;
    }
}
