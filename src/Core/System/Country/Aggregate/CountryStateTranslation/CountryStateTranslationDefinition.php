<?php declare(strict_types=1);

namespace Cicada\Core\System\Country\Aggregate\CountryStateTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;

#[Package('fundamentals@discovery')]
class CountryStateTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'country_state_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CountryStateTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return CountryStateTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return CountryStateDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}
