<?php declare(strict_types=1);

namespace Cicada\Core\System\StateMachine;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionDefinition;

#[Package('checkout')]
class StateMachineDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'state_machine';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return StateMachineEntity::class;
    }

    public function getCollectionClass(): string
    {
        return StateMachineCollection::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new StringField('technical_name', 'technicalName'))->addFlags(new Required()),
            (new TranslatedField('name'))->addFlags(new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            new TranslatedField('customFields'),

            (new OneToManyAssociationField('states', StateMachineStateDefinition::class, 'state_machine_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new OneToManyAssociationField('transitions', StateMachineTransitionDefinition::class, 'state_machine_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            new FkField('initial_state_id', 'initialStateId', StateMachineStateDefinition::class),

            (new TranslationsAssociationField(StateMachineTranslationDefinition::class, 'state_machine_id'))->addFlags(new CascadeDelete(), new Required()),
            new OneToManyAssociationField('historyEntries', StateMachineHistoryDefinition::class, 'state_machine_id'),
        ]);
    }
}
