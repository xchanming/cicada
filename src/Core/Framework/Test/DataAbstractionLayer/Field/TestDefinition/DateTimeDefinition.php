<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class DateTimeDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'date_time_test';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey()),
            new StringField('name', 'name'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
