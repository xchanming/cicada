<?php declare(strict_types=1);

namespace Cicada\Administration\Snippet;

use Cicada\Core\Framework\App\AppDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowEmptyString;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Locale\LocaleDefinition;

#[Package('discovery')]
class AppAdministrationSnippetDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'app_administration_snippet';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return AppAdministrationSnippetCollection::class;
    }

    public function getEntityClass(): string
    {
        return AppAdministrationSnippetEntity::class;
    }

    public function since(): ?string
    {
        return '6.4.15.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new LongTextField('value', 'value'))->addFlags(new ApiAware(), new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING), new AllowEmptyString()),

            (new FkField('app_id', 'appId', AppDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new FkField('locale_id', 'localeId', LocaleDefinition::class))->addFlags(new ApiAware(), new Required()),
        ]);
    }
}
