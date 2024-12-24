<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\Framework\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class TestEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'test_entity';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): string
    {
        return 'test';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey()),
            (new IdField('idAllowHtml', 'idAllowHtml'))->addFlags(new AllowHtml(false)),
            (new IdField('idAllowHtmlSanitized', 'idAllowHtmlSanitized'))->addFlags(new AllowHtml(true)),
        ]);
    }
}
