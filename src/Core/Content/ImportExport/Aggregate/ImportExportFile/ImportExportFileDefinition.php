<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Aggregate\ImportExportFile;

use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class ImportExportFileDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'import_export_file';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ImportExportFileEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('original_name', 'originalName'))->addFlags(new Required()),
            (new StringField('path', 'path'))->addFlags(new Required()),
            (new DateTimeField('expire_date', 'expireDate'))->addFlags(new Required()),
            new IntField('size', 'size'),
            (new OneToOneAssociationField('log', 'id', 'file_id', ImportExportLogDefinition::class, false))->addFlags(new CascadeDelete()),
            new StringField('access_token', 'accessToken'),
        ]);
    }
}
