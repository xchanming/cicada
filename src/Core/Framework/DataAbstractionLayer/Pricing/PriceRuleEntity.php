<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Pricing;

use Cicada\Core\Framework\DataAbstractionLayer\Contract\IdAware;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class PriceRuleEntity extends Entity implements IdAware
{
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $ruleId;

    /**
     * @var PriceCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $price;

    public function getRuleId(): string
    {
        return $this->ruleId;
    }

    public function setRuleId(string $ruleId): void
    {
        $this->ruleId = $ruleId;
    }

    public function getPrice(): PriceCollection
    {
        return $this->price;
    }

    public function setPrice(PriceCollection $price): void
    {
        $this->price = $price;
    }
}
