<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow;

use Cicada\Core\Content\Flow\Aggregate\FlowSequence\FlowSequenceDefinition;
use Cicada\Core\Framework\App\Aggregate\FlowEvent\AppFlowEventDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class FlowDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'flow';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FlowCollection::class;
    }

    public function getEntityClass(): string
    {
        return FlowEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'active' => false,
            'priority' => 1,
        ];
    }

    public function since(): ?string
    {
        return '6.4.6.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name', 255))->addFlags(new Required()),
            (new StringField('event_name', 'eventName', 255))->addFlags(new Required()),
            new IntField('priority', 'priority'),
            (new BlobField('payload', 'payload'))->removeFlag(ApiAware::class)->addFlags(new WriteProtected(Context::SYSTEM_SCOPE)),
            (new BoolField('invalid', 'invalid'))->addFlags(new WriteProtected(Context::SYSTEM_SCOPE)),
            new BoolField('active', 'active'),
            new StringField('description', 'description', 500),
            (new OneToManyAssociationField('sequences', FlowSequenceDefinition::class, 'flow_id', 'id'))->addFlags(new CascadeDelete()),
            new CustomFields(),
            new FkField('app_flow_event_id', 'appFlowEventId', AppFlowEventDefinition::class),
            new ManyToOneAssociationField('appFlowEvent', 'app_flow_event_id', AppFlowEventDefinition::class, 'id', false),
        ]);
    }
}
