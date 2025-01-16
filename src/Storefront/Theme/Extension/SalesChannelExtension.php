<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Extension;

use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Storefront\Theme\Aggregate\ThemeSalesChannelDefinition;
use Cicada\Storefront\Theme\ThemeDefinition;

#[Package('framework')]
class SalesChannelExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new ManyToManyAssociationField('themes', ThemeDefinition::class, ThemeSalesChannelDefinition::class, 'sales_channel_id', 'theme_id')
        );
    }

    public function getDefinitionClass(): string
    {
        return SalesChannelDefinition::class;
    }

    public function getEntityName(): string
    {
        return SalesChannelDefinition::ENTITY_NAME;
    }
}
