<?php declare(strict_types=1);

namespace Cicada\Core\System\StateMachine\Aggregation\StateMachineState;

use Cicada\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class StateMachineStateTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'state_machine_state_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return StateMachineStateTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return StateMachineStateDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([(new StringField('name', 'name'))->addFlags(new Required()), new CustomFields()]);
    }
}
