<?php declare(strict_types=1);

namespace Cicada\Core\System\Tag;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerTag\CustomerTagDefinition;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTag\OrderTagDefinition;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Shipping\Aggregate\ShippingMethodTag\ShippingMethodTagDefinition;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Content\Category\Aggregate\CategoryTag\CategoryTagDefinition;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\LandingPage\Aggregate\LandingPageTag\LandingPageTagDefinition;
use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Content\Media\Aggregate\MediaTag\MediaTagDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipientTag\NewsletterRecipientTagDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductTag\ProductTagDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Rule\Aggregate\RuleTag\RuleTagDefinition;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class TagDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'tag';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return TagCollection::class;
    }

    public function getEntityClass(): string
    {
        return TagEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        $collection = new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new StringField('name', 'name'))->addFlags(new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING), new ApiAware()),

            // reverse side of the associations, not available in sales-channel-api
            (new ManyToManyAssociationField('products', ProductDefinition::class, ProductTagDefinition::class, 'tag_id', 'product_id'))->addFlags(new CascadeDelete()),
            (new ManyToManyAssociationField('media', MediaDefinition::class, MediaTagDefinition::class, 'tag_id', 'media_id'))->addFlags(new CascadeDelete()),
            (new ManyToManyAssociationField('categories', CategoryDefinition::class, CategoryTagDefinition::class, 'tag_id', 'category_id'))->addFlags(new CascadeDelete()),
            (new ManyToManyAssociationField('customers', CustomerDefinition::class, CustomerTagDefinition::class, 'tag_id', 'customer_id'))->addFlags(new CascadeDelete()),
            (new ManyToManyAssociationField('orders', OrderDefinition::class, OrderTagDefinition::class, 'tag_id', 'order_id'))->addFlags(new CascadeDelete()),
            (new ManyToManyAssociationField('shippingMethods', ShippingMethodDefinition::class, ShippingMethodTagDefinition::class, 'tag_id', 'shipping_method_id'))->addFlags(new CascadeDelete()),
            (new ManyToManyAssociationField('newsletterRecipients', NewsletterRecipientDefinition::class, NewsletterRecipientTagDefinition::class, 'tag_id', 'newsletter_recipient_id'))->addFlags(new CascadeDelete()),
            (new ManyToManyAssociationField('landingPages', LandingPageDefinition::class, LandingPageTagDefinition::class, 'tag_id', 'landing_page_id'))->addFlags(new CascadeDelete()),
            (new ManyToManyAssociationField('rules', RuleDefinition::class, RuleTagDefinition::class, 'tag_id', 'rule_id'))->addFlags(new CascadeDelete()),
        ]);

        return $collection;
    }
}
