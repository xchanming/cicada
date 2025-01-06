<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Aggregate\PromotionSetGroup;

use Cicada\Core\Checkout\Promotion\PromotionEntity;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class PromotionSetGroupEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $packagerKey;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $sorterKey;

    /**
     * @var float
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $value;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $promotionId;

    /**
     * @var PromotionEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $promotion;

    /**
     * @var RuleCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $setGroupRules;

    public function getPackagerKey(): string
    {
        return $this->packagerKey;
    }

    public function setPackagerKey(string $packagerKey): void
    {
        $this->packagerKey = $packagerKey;
    }

    public function getSorterKey(): string
    {
        return $this->sorterKey;
    }

    public function setSorterKey(string $sorterKey): void
    {
        $this->sorterKey = $sorterKey;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function getPromotionId(): string
    {
        return $this->promotionId;
    }

    public function setPromotionId(string $promotionId): void
    {
        $this->promotionId = $promotionId;
    }

    public function getPromotion(): ?PromotionEntity
    {
        return $this->promotion;
    }

    public function setPromotion(?PromotionEntity $promotion): void
    {
        $this->promotion = $promotion;
    }

    public function getSetGroupRules(): ?RuleCollection
    {
        return $this->setGroupRules;
    }

    public function setSetGroupRules(RuleCollection $setGroupRules): void
    {
        $this->setGroupRules = $setGroupRules;
    }
}
