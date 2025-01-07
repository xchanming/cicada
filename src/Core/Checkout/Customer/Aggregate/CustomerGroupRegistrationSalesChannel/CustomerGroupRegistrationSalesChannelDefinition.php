<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Aggregate\CustomerGroupRegistrationSalesChannel;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;

#[Package('discovery')]
class CustomerGroupRegistrationSalesChannelDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'customer_group_registration_sales_channels';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.3.1.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('customer_group_id', 'customerGroupId', CustomerGroupDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('customerGroup', 'customer_group_id', CustomerGroupDefinition::class, 'id'),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id'),
            new CreatedAtField(),
        ]);
    }
}
