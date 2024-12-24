<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Write\Entity;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class DefaultsChildDefinition extends EntityDefinition
{
    final public const SCHEMA = 'CREATE TABLE IF NOT EXISTS  `defaults_child` (
        `id` BINARY(16) NOT NULL PRIMARY KEY,
        `defaults_id` BINARY(16) NOT NULL,
        `foo` text NOT NULL,
        `created_at` DATETIME NOT NULL
    )';

    public function getEntityName(): string
    {
        return 'defaults_child';
    }

    public function since(): ?string
    {
        return '6.4.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey()),
            new FkField('defaults_id', 'defaultsId', DefaultsDefinition::class),
            (new StringField('foo', 'foo'))->addFlags(new Required()),
            new TranslatedField('name'),
            new TranslationsAssociationField(DefaultsChildTranslationDefinition::class, 'defaults_child_id'),
            new ManyToOneAssociationField('defaults', 'defaults_id', DefaultsDefinition::class),
        ]);
    }
}
