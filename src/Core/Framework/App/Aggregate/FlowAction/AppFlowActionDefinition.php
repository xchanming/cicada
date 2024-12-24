<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Aggregate\FlowAction;

use Cicada\Core\Content\Flow\Aggregate\FlowSequence\FlowSequenceDefinition;
use Cicada\Core\Framework\App\Aggregate\FlowActionTranslation\AppFlowActionTranslationDefinition;
use Cicada\Core\Framework\App\AppDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ListField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class AppFlowActionDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'app_flow_action';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return AppFlowActionCollection::class;
    }

    public function getEntityClass(): string
    {
        return AppFlowActionEntity::class;
    }

    public function since(): ?string
    {
        return '6.4.10.0';
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
            new StringField('badge', 'badge', 255),
            new JsonField('parameters', 'parameters'),
            new JsonField('config', 'config'),
            new JsonField('headers', 'headers'),
            new ListField('requirements', 'requirements', StringField::class),
            new BlobField('icon', 'iconRaw'),
            (new StringField('icon', 'icon'))->addFlags(new WriteProtected(), new Runtime()),
            new StringField('sw_icon', 'swIcon'),
            (new StringField('url', 'url'))->addFlags(new Required()),
            new BoolField('delayable', 'delayable'),
            new TranslatedField('label'),
            new TranslatedField('description'),
            new TranslatedField('headline'),
            new TranslatedField('customFields'),
            (new TranslationsAssociationField(AppFlowActionTranslationDefinition::class, 'app_flow_action_id'))->addFlags(new Required()),
            new ManyToOneAssociationField('app', 'app_id', AppDefinition::class, 'id', false),
            (new OneToManyAssociationField('flowSequences', FlowSequenceDefinition::class, 'app_flow_action_id'))->addFlags(new CascadeDelete()),
        ]);
    }
}
