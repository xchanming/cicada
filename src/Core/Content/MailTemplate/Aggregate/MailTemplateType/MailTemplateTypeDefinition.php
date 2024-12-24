<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateType;

use Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateTypeTranslation\MailTemplateTypeTranslationDefinition;
use Cicada\Core\Content\MailTemplate\MailTemplateDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class MailTemplateTypeDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'mail_template_type';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MailTemplateTypeEntity::class;
    }

    public function getCollectionClass(): string
    {
        return MailTemplateTypeCollection::class;
    }

    public function getTranslationDefinitionClass(): ?string
    {
        return MailTemplateTypeTranslationDefinition::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),

            (new TranslatedField('name'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new StringField('technical_name', 'technicalName'))->addFlags(new ApiAware(), new Required()),
            new JsonField('available_entities', 'availableEntities'),
            (new TranslatedField('customFields'))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(MailTemplateTypeTranslationDefinition::class, 'mail_template_type_id'))->addFlags(new ApiAware(), new Required()),
            (new OneToManyAssociationField('mailTemplates', MailTemplateDefinition::class, 'mail_template_type_id'))->addFlags(new SetNullOnDelete()),
            new JsonField('template_data', 'templateData'),
        ]);
    }
}
