<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Shipping;

use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Cicada\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice\ShippingMethodPriceDefinition;
use Cicada\Core\Checkout\Shipping\Aggregate\ShippingMethodTag\ShippingMethodTagDefinition;
use Cicada\Core\Checkout\Shipping\Aggregate\ShippingMethodTranslation\ShippingMethodTranslationDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Framework\App\Aggregate\AppShippingMethod\AppShippingMethodDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\DeliveryTime\DeliveryTimeDefinition;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelShippingMethod\SalesChannelShippingMethodDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\Tag\TagDefinition;
use Cicada\Core\System\Tax\TaxDefinition;

#[Package('checkout')]
class ShippingMethodDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'shipping_method';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ShippingMethodCollection::class;
    }

    public function getEntityClass(): string
    {
        return ShippingMethodEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'taxType' => ShippingMethodEntity::TAX_TYPE_AUTO,
            'position' => ShippingMethodEntity::POSITION_DEFAULT,
            'active' => ShippingMethodEntity::ACTIVE_DEFAULT,
        ];
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        $fields = new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new TranslatedField('name'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new IntField('position', 'position'))->addFlags(new ApiAware()),
            (new TranslatedField('customFields'))->addFlags(new ApiAware()),
            new FkField('availability_rule_id', 'availabilityRuleId', RuleDefinition::class),
            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new FkField('delivery_time_id', 'deliveryTimeId', DeliveryTimeDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new StringField('tax_type', 'taxType', 50))->addFlags(new ApiAware(), new Required()),
            new FkField('tax_id', 'taxId', TaxDefinition::class),
            (new ManyToOneAssociationField('deliveryTime', 'delivery_time_id', DeliveryTimeDefinition::class, 'id', !Feature::isActive('v6.7.0.0')))->addFlags(new ApiAware()),
            (new TranslatedField('description'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::LOW_SEARCH_RANKING)),
            (new TranslatedField('trackingUrl'))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(ShippingMethodTranslationDefinition::class, 'shipping_method_id'))->addFlags(new ApiAware(), new Required()),
            (new ManyToOneAssociationField('availabilityRule', 'availability_rule_id', RuleDefinition::class))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('prices', ShippingMethodPriceDefinition::class, 'shipping_method_id', 'id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class))->addFlags(new ApiAware()),
            (new ManyToManyAssociationField('tags', TagDefinition::class, ShippingMethodTagDefinition::class, 'shipping_method_id', 'tag_id'))->addFlags(new ApiAware()),

            // Reverse Association, not available in sales-channel-api
            (new OneToManyAssociationField('orderDeliveries', OrderDeliveryDefinition::class, 'shipping_method_id', 'id'))->addFlags(new RestrictDelete()),
            new ManyToManyAssociationField('salesChannels', SalesChannelDefinition::class, SalesChannelShippingMethodDefinition::class, 'shipping_method_id', 'sales_channel_id'),
            (new OneToManyAssociationField('salesChannelDefaultAssignments', SalesChannelDefinition::class, 'shipping_method_id', 'id'))->addFlags(new RestrictDelete()),
            (new ManyToOneAssociationField('tax', 'tax_id', TaxDefinition::class))->addFlags(new ApiAware()),
            (new OneToOneAssociationField('appShippingMethod', 'id', 'shipping_method_id', AppShippingMethodDefinition::class, !Feature::isActive('v6.7.0.0')))->addFlags(new CascadeDelete()),
        ]);

        if (Feature::isActive('v6.7.0.0')) {
            $fields->add((new StringField('technical_name', 'technicalName'))->addFlags(new ApiAware(), new Required()));
        } else {
            $fields->add((new StringField('technical_name', 'technicalName'))->addFlags(new ApiAware()));
        }

        return $fields;
    }
}
