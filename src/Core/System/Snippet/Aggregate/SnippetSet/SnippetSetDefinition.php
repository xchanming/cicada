<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Aggregate\SnippetSet;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
use Cicada\Core\System\Snippet\SnippetDefinition;

#[Package('discovery')]
class SnippetSetDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'snippet_set';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SnippetSetCollection::class;
    }

    public function getEntityClass(): string
    {
        return SnippetSetEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new StringField('base_file', 'baseFile'))->addFlags(new Required()),
            (new StringField('iso', 'iso'))->addFlags(new ApiAware(), new Required()),
            (new CustomFields())->addFlags(new ApiAware()),
            (new OneToManyAssociationField('snippets', SnippetDefinition::class, 'snippet_set_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new OneToManyAssociationField('salesChannelDomains', SalesChannelDomainDefinition::class, 'snippet_set_id'))->addFlags(new RestrictDelete()),
        ]);
    }
}
