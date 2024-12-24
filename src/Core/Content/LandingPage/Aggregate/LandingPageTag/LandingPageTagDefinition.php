<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Aggregate\LandingPageTag;

use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tag\TagDefinition;

#[Package('buyers-experience')]
class LandingPageTagDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'landing_page_tag';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function isVersionAware(): bool
    {
        return true;
    }

    public function since(): ?string
    {
        return '6.4.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('landing_page_id', 'landingPageId', LandingPageDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(LandingPageDefinition::class))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('tag_id', 'tagId', TagDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('landingPage', 'landing_page_id', LandingPageDefinition::class, 'id', false),
            new ManyToOneAssociationField('tag', 'tag_id', TagDefinition::class, 'id', false),
        ]);
    }
}
