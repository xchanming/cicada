<?php declare(strict_types=1);

namespace Cicada\Core\System\NumberRange;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeSalesChannel\NumberRangeSalesChannelDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeState\NumberRangeStateDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeTranslation\NumberRangeTranslationDefinition;
use Cicada\Core\System\NumberRange\Aggregate\NumberRangeType\NumberRangeTypeDefinition;

#[Package('checkout')]
class NumberRangeDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'number_range';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return NumberRangeCollection::class;
    }

    public function getEntityClass(): string
    {
        return NumberRangeEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('type_id', 'typeId', NumberRangeTypeDefinition::class))->addFlags(new Required()),
            (new BoolField('global', 'global'))->addFlags(new Required()),
            new TranslatedField('name'),
            new TranslatedField('description'),
            (new StringField('pattern', 'pattern'))->addFlags(new Required()),
            (new IntField('start', 'start'))->addFlags(new Required()),
            new TranslatedField('customFields'),

            new ManyToOneAssociationField('type', 'type_id', NumberRangeTypeDefinition::class),
            (new OneToManyAssociationField('numberRangeSalesChannels', NumberRangeSalesChannelDefinition::class, 'number_range_id'))->addFlags(new CascadeDelete()),
            (new OneToOneAssociationField('state', 'id', 'number_range_id', NumberRangeStateDefinition::class, !Feature::isActive('v6.7.0.0')))->addFlags(new CascadeDelete()),
            (new TranslationsAssociationField(NumberRangeTranslationDefinition::class, 'number_range_id'))->addFlags(new Required()),
        ]);
    }
}
