<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Aggregate\SalesChannelTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;

#[Package('discovery')]
class SalesChannelTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'sales_channel_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SalesChannelTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return SalesChannelTranslationEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'homeEnabled' => true,
        ];
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return SalesChannelDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        $fields = new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            new JsonField('home_slot_config', 'homeSlotConfig'),
            (new BoolField('home_enabled', 'homeEnabled'))->addFlags(new Required()),
            new StringField('home_name', 'homeName'),
            new StringField('home_meta_title', 'homeMetaTitle'),
            new StringField('home_meta_description', 'homeMetaDescription'),
            new StringField('home_keywords', 'homeKeywords'),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);

        return $fields;
    }
}
