<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\Serializer\_fixtures;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\FieldType;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\ForeignKey;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\ManyToMany;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\ManyToOne;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\OnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Attribute\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;

/**
 * @internal
 */
class TestAttributeEntity extends Entity
{
    #[PrimaryKey]
    #[Field(type: FieldType::UUID)]
    public string $id;

    #[ForeignKey(entity: 'customer')]
    public ?string $customerId = null;

    /**
     * @var array<string, ProductEntity>|null
     */
    #[ManyToMany(entity: 'product', onDelete: OnDelete::CASCADE)]
    public ?array $products = null;

    #[ManyToOne(entity: 'customer', onDelete: OnDelete::SET_NULL)]
    public ?CustomerEntity $customer;
}
