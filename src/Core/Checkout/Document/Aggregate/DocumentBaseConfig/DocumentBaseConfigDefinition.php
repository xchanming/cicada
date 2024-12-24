<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig;

use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfigSalesChannel\DocumentBaseConfigSalesChannelDefinition;
use Cicada\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;

#[Package('checkout')]
class DocumentBaseConfigDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'document_base_config';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return DocumentBaseConfigCollection::class;
    }

    public function getEntityClass(): string
    {
        return DocumentBaseConfigEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'global' => false,
        ];
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return DocumentTypeDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),

            (new FkField('document_type_id', 'documentTypeId', DocumentTypeDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('logo_id', 'logoId', MediaDefinition::class))->addFlags(new ApiAware()),

            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new StringField('filename_prefix', 'filenamePrefix'))->addFlags(new ApiAware()),
            (new StringField('filename_suffix', 'filenameSuffix'))->addFlags(new ApiAware()),
            (new BoolField('global', 'global'))->addFlags(new ApiAware(), new Required()),
            (new NumberRangeField('document_number', 'documentNumber'))->addFlags(new ApiAware()),
            (new JsonField('config', 'config'))->addFlags(new ApiAware()),
            (new CreatedAtField())->addFlags(new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware()),

            new ManyToOneAssociationField('documentType', 'document_type_id', DocumentTypeDefinition::class, 'id'),
            (new ManyToOneAssociationField('logo', 'logo_id', MediaDefinition::class, 'id'))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('salesChannels', DocumentBaseConfigSalesChannelDefinition::class, 'document_base_config_id', 'id'))->addFlags(new CascadeDelete()),
        ]);
    }
}
