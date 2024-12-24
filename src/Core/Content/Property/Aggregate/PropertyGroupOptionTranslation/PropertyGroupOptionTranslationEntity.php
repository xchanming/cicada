<?php declare(strict_types=1);

namespace Cicada\Core\Content\Property\Aggregate\PropertyGroupOptionTranslation;

use Cicada\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class PropertyGroupOptionTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $propertyGroupOptionId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $name;

    /**
     * @var int|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $position;

    /**
     * @var PropertyGroupOptionEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $propertyGroupOption;

    public function getPropertyGroupOptionId(): string
    {
        return $this->propertyGroupOptionId;
    }

    public function setPropertyGroupOptionId(string $propertyGroupOptionId): void
    {
        $this->propertyGroupOptionId = $propertyGroupOptionId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPropertyGroupOption(): ?PropertyGroupOptionEntity
    {
        return $this->propertyGroupOption;
    }

    public function setPropertyGroupOption(PropertyGroupOptionEntity $propertyGroupOption): void
    {
        $this->propertyGroupOption = $propertyGroupOption;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
