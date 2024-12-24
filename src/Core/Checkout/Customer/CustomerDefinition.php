<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerRecovery\CustomerRecoveryDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerTag\CustomerTagDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionPersonaCustomer\PromotionPersonaCustomerDefinition;
use Cicada\Core\Checkout\Promotion\PromotionDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewDefinition;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AutoIncrementField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\EmailField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\IgnoreInOpenapiSchema;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\NoConstraint;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyIdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\RemoteAddressField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedByField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\System\Tag\TagDefinition;
use Cicada\Core\System\User\UserDefinition;

#[Package('checkout')]
class CustomerDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'customer';

    public const MAX_LENGTH_FIRST_NAME = 255;
    public const MAX_LENGTH_LAST_NAME = 255;
    public const MAX_LENGTH_TITLE = 100;

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CustomerCollection::class;
    }

    public function getEntityClass(): string
    {
        return CustomerEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
        ];
    }

    public function hasManyToManyIdFields(): bool
    {
        return true;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        $fields = new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new FkField('customer_group_id', 'groupId', CustomerGroupDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('last_payment_method_id', 'lastPaymentMethodId', PaymentMethodDefinition::class))->addFlags(new ApiAware()),
            (new FkField('default_billing_address_id', 'defaultBillingAddressId', CustomerAddressDefinition::class))->addFlags(new ApiAware(), new Required(), new NoConstraint()),
            (new FkField('default_shipping_address_id', 'defaultShippingAddressId', CustomerAddressDefinition::class))->addFlags(new ApiAware(), new Required(), new NoConstraint()),
            new AutoIncrementField(),
            (new NumberRangeField('customer_number', 'customerNumber', 255))->addFlags(new ApiAware(), new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new FkField('salutation_id', 'salutationId', SalutationDefinition::class))->addFlags(new ApiAware()),
            (new StringField('first_name', 'firstName', self::MAX_LENGTH_FIRST_NAME))->addFlags(new ApiAware(), new Required(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new StringField('last_name', 'lastName', self::MAX_LENGTH_LAST_NAME))->addFlags(new ApiAware(), new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new StringField('company', 'company'))->addFlags(new ApiAware(), new IgnoreInOpenapiSchema(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new PasswordField('password', 'password', \PASSWORD_DEFAULT, [], PasswordField::FOR_CUSTOMER))->removeFlag(ApiAware::class),
            (new EmailField('email', 'email'))->addFlags(new ApiAware(), new Required(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING, false)),
            (new StringField('title', 'title', self::MAX_LENGTH_TITLE))->addFlags(new ApiAware()),
            (new ListField('vat_ids', 'vatIds', StringField::class))->addFlags(new ApiAware(), new IgnoreInOpenapiSchema()),
            (new StringField('affiliate_code', 'affiliateCode'))->addFlags(new ApiAware()),
            (new StringField('campaign_code', 'campaignCode'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new BoolField('double_opt_in_registration', 'doubleOptInRegistration'))->addFlags(new ApiAware()),
            (new DateTimeField('double_opt_in_email_sent_date', 'doubleOptInEmailSentDate'))->addFlags(new ApiAware()),
            (new DateTimeField('double_opt_in_confirm_date', 'doubleOptInConfirmDate'))->addFlags(new ApiAware()),
            (new StringField('hash', 'hash'))->addFlags(new ApiAware()),
            (new BoolField('guest', 'guest'))->addFlags(new ApiAware()),
            (new DateTimeField('first_login', 'firstLogin'))->addFlags(new ApiAware()),
            (new DateTimeField('last_login', 'lastLogin'))->addFlags(new ApiAware()),
            (new JsonField('newsletter_sales_channel_ids', 'newsletterSalesChannelIds'))->addFlags(new WriteProtected(Context::SYSTEM_SCOPE))->removeFlag(ApiAware::class),
            (new DateField('birthday', 'birthday'))->addFlags(new ApiAware()),
            (new DateTimeField('last_order_date', 'lastOrderDate'))->addFlags(new ApiAware(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new IntField('order_count', 'orderCount'))->addFlags(new ApiAware(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new FloatField('order_total_amount', 'orderTotalAmount'))->addFlags(new ApiAware(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new IntField('review_count', 'reviewCount'))->addFlags(new ApiAware(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new CustomFields())->addFlags(new ApiAware()),
            (new StringField('legacy_password', 'legacyPassword'))->removeFlag(ApiAware::class),
            (new StringField('legacy_encoder', 'legacyEncoder'))->removeFlag(ApiAware::class),
            (new ManyToOneAssociationField('group', 'customer_group_id', CustomerGroupDefinition::class, 'id', false))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            (new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('lastPaymentMethod', 'last_payment_method_id', PaymentMethodDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('defaultBillingAddress', 'default_billing_address_id', CustomerAddressDefinition::class, 'id', false))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)),
            (new ManyToOneAssociationField('activeBillingAddress', 'active_billing_address_id', CustomerAddressDefinition::class, 'id', false))->addFlags(new ApiAware(SalesChannelApiSource::class), new Runtime()),
            (new ManyToOneAssociationField('defaultShippingAddress', 'default_shipping_address_id', CustomerAddressDefinition::class, 'id', false))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)),
            (new ManyToOneAssociationField('activeShippingAddress', 'active_shipping_address_id', CustomerAddressDefinition::class, 'id', false))->addFlags(new ApiAware(SalesChannelApiSource::class), new Runtime()),
            (new ManyToOneAssociationField('salutation', 'salutation_id', SalutationDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('addresses', CustomerAddressDefinition::class, 'customer_id', 'id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new OneToManyAssociationField('orderCustomers', OrderCustomerDefinition::class, 'customer_id', 'id'))->addFlags(new SetNullOnDelete()),
            (new ManyToManyAssociationField('tags', TagDefinition::class, CustomerTagDefinition::class, 'customer_id', 'tag_id'))->addFlags(new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING), new ApiAware()),
            new ManyToManyAssociationField('promotions', PromotionDefinition::class, PromotionPersonaCustomerDefinition::class, 'customer_id', 'promotion_id'),
            new OneToManyAssociationField('productReviews', ProductReviewDefinition::class, 'customer_id'),
            new OneToOneAssociationField('recoveryCustomer', 'id', 'customer_id', CustomerRecoveryDefinition::class, false),
            new RemoteAddressField('remote_address', 'remoteAddress'),
            (new ManyToManyIdField('tag_ids', 'tagIds', 'tags'))->addFlags(new ApiAware()),
            new FkField('requested_customer_group_id', 'requestedGroupId', CustomerGroupDefinition::class),
            new ManyToOneAssociationField('requestedGroup', 'requested_customer_group_id', CustomerGroupDefinition::class, 'id', false),
            new FkField('bound_sales_channel_id', 'boundSalesChannelId', SalesChannelDefinition::class),
            (new StringField('account_type', 'accountType'))->addFlags(new ApiAware(), new Required(), new IgnoreInOpenapiSchema()),
            new ManyToOneAssociationField('boundSalesChannel', 'bound_sales_channel_id', SalesChannelDefinition::class, 'id', false),
            (new OneToManyAssociationField('wishlists', CustomerWishlistDefinition::class, 'customer_id'))->addFlags(new CascadeDelete()),
            (new CreatedByField([Context::SYSTEM_SCOPE, Context::CRUD_API_SCOPE]))->addFlags(new ApiAware()),
            (new UpdatedByField([Context::SYSTEM_SCOPE, Context::CRUD_API_SCOPE]))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('createdBy', 'created_by_id', UserDefinition::class, 'id', false),
            new ManyToOneAssociationField('updatedBy', 'updated_by_id', UserDefinition::class, 'id', false),
        ]);

        if (!Feature::isActive('v6.7.0.0')) {
            $fields->add((new FkField('default_payment_method_id', 'defaultPaymentMethodId', PaymentMethodDefinition::class))->addFlags(new ApiAware(), new Required()));
            $fields->add((new ManyToOneAssociationField('defaultPaymentMethod', 'default_payment_method_id', PaymentMethodDefinition::class, 'id', false))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)));
        }

        return $fields;
    }
}
