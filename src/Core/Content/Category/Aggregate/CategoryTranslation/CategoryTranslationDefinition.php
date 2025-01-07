<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Aggregate\CategoryTranslation;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BreadcrumbField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class CategoryTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'category_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CategoryTranslationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CategoryTranslationCollection::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return CategoryDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new BreadcrumbField())->addFlags(new ApiAware(), new WriteProtected()),
            new JsonField('slot_config', 'slotConfig'),
            (new StringField('link_type', 'linkType'))->addFlags(new ApiAware()),
            (new IdField('internal_link', 'internalLink'))->addFlags(new ApiAware()),
            (new StringField('external_link', 'externalLink'))->addFlags(new ApiAware()),
            (new BoolField('link_new_tab', 'linkNewTab'))->addFlags(new ApiAware()),
            (new LongTextField('description', 'description'))->addFlags(new ApiAware(), new AllowHtml()),
            (new LongTextField('meta_title', 'metaTitle'))->addFlags(new ApiAware()),
            (new LongTextField('meta_description', 'metaDescription'))->addFlags(new ApiAware()),
            (new LongTextField('keywords', 'keywords'))->addFlags(new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}
