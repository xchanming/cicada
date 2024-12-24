<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting;

use Cicada\Core\Framework\DataAbstractionLayer\Search\CriteriaPartInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('core')]
class FieldSorting extends Struct implements CriteriaPartInterface
{
    public const ASCENDING = 'ASC';
    public const DESCENDING = 'DESC';

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $field;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $direction;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $naturalSorting;

    public function __construct(
        string $field,
        string $direction = self::ASCENDING,
        bool $naturalSorting = false
    ) {
        $this->field = $field;
        $this->direction = $direction;
        $this->naturalSorting = $naturalSorting;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getFields(): array
    {
        return [$this->field];
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getNaturalSorting(): bool
    {
        return $this->naturalSorting;
    }

    public function getApiAlias(): string
    {
        return 'dal_field_sorting';
    }
}
