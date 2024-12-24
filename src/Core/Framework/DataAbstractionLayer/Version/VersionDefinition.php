<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Version;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Version\Aggregate\VersionCommit\VersionCommitDefinition;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class VersionDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'version';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function isVersionAware(): bool
    {
        return false;
    }

    public function getCollectionClass(): string
    {
        return VersionCollection::class;
    }

    public function getEntityClass(): string
    {
        return VersionEntity::class;
    }

    public function getDefaults(): array
    {
        $dateTime = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        return ['name' => \sprintf('Draft %s', $dateTime), 'createdAt' => $dateTime];
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            new OneToManyAssociationField('commits', VersionCommitDefinition::class, 'version_id'),
        ]);
    }
}
