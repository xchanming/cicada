<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order;

use Cicada\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTag\OrderTagDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AutoIncrementField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CashRoundingConfigField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowEmptyString;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\NoConstraint;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StateMachineStateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedByField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyDefinition;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Cicada\Core\System\Tag\TagDefinition;
use Cicada\Core\System\User\UserDefinition;

#[Package('checkout')]
class OrderDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'order';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return OrderCollection::class;
    }

    public function getEntityClass(): string
    {
        return OrderEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new VersionField())->addFlags(new ApiAware()),

            new AutoIncrementField(),

            (new NumberRangeField('order_number', 'orderNumber'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING, false)),

            (new FkField('billing_address_id', 'billingAddressId', OrderAddressDefinition::class))->addFlags(new ApiAware(), new NoConstraint()),

            (new FkField('currency_id', 'currencyId', CurrencyDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new ApiAware(), new Required()),

            (new DateTimeField('order_date_time', 'orderDateTime'))->addFlags(new ApiAware(), new Required()),
            (new DateField('order_date', 'orderDate'))->addFlags(new ApiAware(), new WriteProtected()),
            (new CartPriceField('price', 'price'))->addFlags(new ApiAware()),
            (new FloatField('amount_total', 'amountTotal'))->addFlags(new ApiAware(), new WriteProtected(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new FloatField('amount_net', 'amountNet'))->addFlags(new ApiAware(), new WriteProtected()),
            (new FloatField('position_price', 'positionPrice'))->addFlags(new ApiAware(), new WriteProtected()),
            (new StringField('tax_status', 'taxStatus'))->addFlags(new ApiAware(), new WriteProtected()),
            (new CalculatedPriceField('shipping_costs', 'shippingCosts'))->addFlags(new ApiAware()),
            (new FloatField('shipping_total', 'shippingTotal'))->addFlags(new ApiAware(), new WriteProtected()),
            (new FloatField('currency_factor', 'currencyFactor'))->addFlags(new ApiAware(), new Required()),
            (new StringField('deep_link_code', 'deepLinkCode'))->addFlags(new ApiAware()),
            (new StringField('affiliate_code', 'affiliateCode'))->addFlags(new ApiAware()),
            (new StringField('campaign_code', 'campaignCode'))->addFlags(new ApiAware()),
            (new LongTextField('customer_comment', 'customerComment'))->addFlags(new ApiAware(), new AllowEmptyString()),
            (new StringField('source', 'source'))->addFlags(new ApiAware()),

            (new StateMachineStateField('state_id', 'stateId', OrderStates::STATE_MACHINE))->addFlags(new Required()),
            (new ManyToOneAssociationField('stateMachineState', 'state_id', StateMachineStateDefinition::class, 'id'))->addFlags(new ApiAware()),
            new ListField('rule_ids', 'ruleIds', StringField::class),
            (new CustomFields())->addFlags(new ApiAware()),
            (new CreatedByField())->addFlags(new ApiAware()),
            (new UpdatedByField())->addFlags(new ApiAware()),

            (new OneToOneAssociationField('orderCustomer', 'id', 'order_id', OrderCustomerDefinition::class))->addFlags(new ApiAware(), new CascadeDelete(), new SearchRanking(0.5)),
            (new ManyToOneAssociationField('currency', 'currency_id', CurrencyDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            (new OneToManyAssociationField('addresses', OrderAddressDefinition::class, 'order_id'))->addFlags(new ApiAware(), new CascadeDelete(), new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)),
            (new ManyToOneAssociationField('billingAddress', 'billing_address_id', OrderAddressDefinition::class))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('deliveries', OrderDeliveryDefinition::class, 'order_id'))->addFlags(new ApiAware(), new CascadeDelete(), new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)),
            (new OneToManyAssociationField('lineItems', OrderLineItemDefinition::class, 'order_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new OneToManyAssociationField('transactions', OrderTransactionDefinition::class, 'order_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new ManyToManyAssociationField('tags', TagDefinition::class, OrderTagDefinition::class, 'order_id', 'tag_id'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)),
            new ManyToOneAssociationField('createdBy', 'created_by_id', UserDefinition::class, 'id', false),
            new ManyToOneAssociationField('updatedBy', 'updated_by_id', UserDefinition::class, 'id', false),
            (new CashRoundingConfigField('item_rounding', 'itemRounding'))->addFlags(new Required()),
            (new CashRoundingConfigField('total_rounding', 'totalRounding'))->addFlags(new Required()),
        ]);
    }
}
