<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\FlowEvent;

use Cicada\Core\Content\Flow\FlowDefinition;
use Cicada\Core\Framework\App\AppDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class AppFlowEventDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'app_flow_event';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return AppFlowEventCollection::class;
    }

    public function getEntityClass(): string
    {
        return AppFlowEventEntity::class;
    }

    public function since(): ?string
    {
        return '6.5.2.0';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return AppDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('app_id', 'appId', AppDefinition::class))->addFlags(new Required()),
            (new StringField('name', 'name', 255))->addFlags(new Required()),
            (new ListField('aware', 'aware', StringField::class))->addFlags(new Required()),
            new CustomFields(),
            new ManyToOneAssociationField('app', 'app_id', AppDefinition::class, 'id', false),
            (new OneToManyAssociationField('flows', FlowDefinition::class, 'app_flow_event_id'))->addFlags(new CascadeDelete()),
        ]);
    }
}
