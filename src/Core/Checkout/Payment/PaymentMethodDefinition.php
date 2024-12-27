<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Framework\App\Aggregate\AppPaymentMethod\AppPaymentMethodDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
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
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginDefinition;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelPaymentMethod\SalesChannelPaymentMethodDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;

#[Package('checkout')]
class PaymentMethodDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'payment_method';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return PaymentMethodCollection::class;
    }

    public function getEntityClass(): string
    {
        return PaymentMethodEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        $fields = new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            new FkField('plugin_id', 'pluginId', PluginDefinition::class),
            new StringField('handler_identifier', 'handlerIdentifier'),
            (new TranslatedField('name'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new TranslatedField('distinguishableName'))->addFlags(new ApiAware(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new TranslatedField('description'))->addFlags(new ApiAware()),
            (new IntField('position', 'position'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new BoolField('after_order_enabled', 'afterOrderEnabled'))->addFlags(new ApiAware()),
            (new TranslatedField('customFields'))->addFlags(new ApiAware()),
            new FkField('availability_rule_id', 'availabilityRuleId', RuleDefinition::class),
            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new StringField('formatted_handler_identifier', 'formattedHandlerIdentifier'))->addFlags(new WriteProtected(), new Runtime()),
            (new BoolField('synchronous', 'synchronous'))->addFlags(new ApiAware(), new WriteProtected(), new Runtime()),
            (new BoolField('asynchronous', 'asynchronous'))->addFlags(new ApiAware(), new WriteProtected(), new Runtime()),
            (new BoolField('prepared', 'prepared'))->addFlags(new ApiAware(), new WriteProtected(), new Runtime()),
            (new BoolField('refundable', 'refundable'))->addFlags(new ApiAware(), new WriteProtected(), new Runtime()),
            (new BoolField('recurring', 'recurring'))->addFlags(new ApiAware(), new WriteProtected(), new Runtime()),

            (new TranslationsAssociationField(PaymentMethodTranslationDefinition::class, 'payment_method_id'))->addFlags(new ApiAware(), new Required()),
            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('availabilityRule', 'availability_rule_id', RuleDefinition::class, 'id'),

            // Reverse Associations, not available in store-api
            (new OneToManyAssociationField('salesChannelDefaultAssignments', SalesChannelDefinition::class, 'payment_method_id', 'id'))->addFlags(new RestrictDelete()),
            new ManyToOneAssociationField('plugin', 'plugin_id', PluginDefinition::class, 'id'),
            (new OneToManyAssociationField('customers', CustomerDefinition::class, 'last_payment_method_id', 'id'))->addFlags(new RestrictDelete()),
            (new OneToManyAssociationField('orderTransactions', OrderTransactionDefinition::class, 'payment_method_id', 'id'))->addFlags(new RestrictDelete()),
            new ManyToManyAssociationField('salesChannels', SalesChannelDefinition::class, SalesChannelPaymentMethodDefinition::class, 'payment_method_id', 'sales_channel_id'),
            (new OneToOneAssociationField('appPaymentMethod', 'id', 'payment_method_id', AppPaymentMethodDefinition::class, false))->addFlags(new CascadeDelete()),

            // runtime fields
            (new StringField('short_name', 'shortName'))->addFlags(new ApiAware(), new Runtime()),
            (new StringField('technical_name', 'technicalName'))->addFlags(new ApiAware(), new Required()),
        ]);

        return $fields;
    }
}
