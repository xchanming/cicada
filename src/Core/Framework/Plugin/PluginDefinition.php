<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin;

use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Aggregate\PluginTranslation\PluginTranslationDefinition;

#[Package('core')]
class PluginDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'plugin';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return PluginCollection::class;
    }

    public function getEntityClass(): string
    {
        return PluginEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('base_class', 'baseClass'))->addFlags(new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            new StringField('composer_name', 'composerName'),
            (new JsonField('autoload', 'autoload'))->addFlags(new Required()),
            new BoolField('active', 'active'),
            new BoolField('managed_by_composer', 'managedByComposer'),
            new StringField('path', 'path'),
            new StringField('author', 'author'),
            new StringField('copyright', 'copyright'),
            new StringField('license', 'license'),
            (new StringField('version', 'version'))->addFlags(new Required()),
            new StringField('upgrade_version', 'upgradeVersion'),
            new DateTimeField('installed_at', 'installedAt'),
            new DateTimeField('upgraded_at', 'upgradedAt'),
            (new BlobField('icon', 'iconRaw'))->removeFlag(ApiAware::class),
            (new StringField('icon', 'icon'))->addFlags(new WriteProtected(), new Runtime()),
            new TranslatedField('label'),
            new TranslatedField('description'),
            new TranslatedField('manufacturerLink'),
            new TranslatedField('supportLink'),
            new TranslatedField('customFields'),

            (new TranslationsAssociationField(PluginTranslationDefinition::class, 'plugin_id'))->addFlags(new Required(), new CascadeDelete()),
            (new OneToManyAssociationField('paymentMethods', PaymentMethodDefinition::class, 'plugin_id', 'id'))->addFlags(new SetNullOnDelete()),
        ]);
    }
}
