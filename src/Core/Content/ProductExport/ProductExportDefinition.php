<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport;

use Cicada\Core\Content\ProductStream\ProductStreamDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyDefinition;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;

#[Package('inventory')]
class ProductExportDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'product_export';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ProductExportCollection::class;
    }

    public function getEntityClass(): string
    {
        return ProductExportEntity::class;
    }

    public function since(): ?string
    {
        return '6.1.0.0';
    }

    public function getHydratorClass(): string
    {
        return ProductExportHydrator::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('product_stream_id', 'productStreamId', ProductStreamDefinition::class))->addFlags(new Required()),
            (new FkField('storefront_sales_channel_id', 'storefrontSalesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            (new FkField('sales_channel_domain_id', 'salesChannelDomainId', SalesChannelDomainDefinition::class))->addFlags(new Required()),
            (new FkField('currency_id', 'currencyId', CurrencyDefinition::class))->addFlags(new Required()),
            (new StringField('file_name', 'fileName'))->addFlags(new Required()),
            (new StringField('access_key', 'accessKey'))->addFlags(new Required()),
            (new StringField('encoding', 'encoding'))->addFlags(new Required()),
            (new StringField('file_format', 'fileFormat'))->addFlags(new Required()),
            new BoolField('include_variants', 'includeVariants'),
            (new BoolField('generate_by_cronjob', 'generateByCronjob'))->addFlags(new Required()),
            new DateTimeField('generated_at', 'generatedAt'),
            (new IntField('interval', 'interval'))->addFlags(new Required()),
            (new LongTextField('header_template', 'headerTemplate'))->addFlags(new AllowHtml(false)),
            (new LongTextField('body_template', 'bodyTemplate'))->addFlags(new AllowHtml(false)),
            (new LongTextField('footer_template', 'footerTemplate'))->addFlags(new AllowHtml(false)),
            new BoolField('paused_schedule', 'pausedSchedule'),
            new BoolField('is_running', 'isRunning'),
            new ManyToOneAssociationField('productStream', 'product_stream_id', ProductStreamDefinition::class, 'id', false),
            new ManyToOneAssociationField('storefrontSalesChannel', 'storefront_sales_channel_id', SalesChannelDefinition::class, 'id', false),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            new ManyToOneAssociationField('salesChannelDomain', 'sales_channel_domain_id', SalesChannelDomainDefinition::class, 'id', false),
            new ManyToOneAssociationField('currency', 'currency_id', CurrencyDefinition::class, 'id', false),
        ]);
    }
}
