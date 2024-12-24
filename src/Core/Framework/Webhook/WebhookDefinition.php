<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Webhook;

use Cicada\Core\Framework\App\AppDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class WebhookDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'webhook';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return WebhookEntity::class;
    }

    public function getCollectionClass(): string
    {
        return WebhookCollection::class;
    }

    public function since(): ?string
    {
        return '6.3.1.0';
    }

    public function getDefaults(): array
    {
        return [
            'active' => true,
            'errorCount' => 0,
            'onlyLiveVersion' => false,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        $collection = new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new StringField('event_name', 'eventName', 500))->addFlags(new Required()),
            (new StringField('url', 'url', 500))->addFlags(new Required()),
            new BoolField('only_live_version', 'onlyLiveVersion'),
            (new IntField('error_count', 'errorCount', 0))->addFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
            new BoolField('active', 'active'),
            new FkField('app_id', 'appId', AppDefinition::class),
            new ManyToOneAssociationField('app', 'app_id', AppDefinition::class),
        ]);

        return $collection;
    }
}
