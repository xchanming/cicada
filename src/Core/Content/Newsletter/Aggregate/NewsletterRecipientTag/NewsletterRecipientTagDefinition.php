<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipientTag;

use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tag\TagDefinition;

#[Package('buyers-experience')]
class NewsletterRecipientTagDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'newsletter_recipient_tag';

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
            (new FkField('newsletter_recipient_id', 'newsletterRecipientId', NewsletterRecipientDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('tag_id', 'tagId', TagDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('newsletterRecipient', 'newsletter_recipient_id', NewsletterRecipientDefinition::class, 'id', false),
            new ManyToOneAssociationField('tag', 'tag_id', TagDefinition::class, 'id', false),
        ]);
    }
}
