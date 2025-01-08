<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DependencyInjection\CompilerPass;

use Cicada\Core\Checkout\Cart\CartDataCollectorInterface;
use Cicada\Core\Checkout\Cart\CartProcessorInterface;
use Cicada\Core\Checkout\Cart\CartValidatorInterface;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupPackagerInterface;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupSorterInterface;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\LineItemFactoryInterface;
use Cicada\Core\Checkout\Cart\TaxProvider\AbstractTaxProvider;
use Cicada\Core\Checkout\Customer\Password\LegacyEncoder\LegacyEncoderInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\FilterPickerInterface;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\FilterSorterInterface;
use Cicada\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Cicada\Core\Content\Flow\Dispatching\Storer\FlowStorer;
use Cicada\Core\Content\Product\SalesChannel\Listing\Filter\AbstractListingFilterHandler;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\AbstractListingProcessor;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Cicada\Core\Content\Sitemap\Provider\AbstractUrlProvider;
use Cicada\Core\Framework\Adapter\Filesystem\Adapter\AdapterFactoryInterface;
use Cicada\Core\Framework\Adapter\Twig\NamespaceHierarchy\TemplateNamespaceHierarchyBuilderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\BulkEntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FieldSerializerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Cicada\Core\Framework\Routing\AbstractRouteScope;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\AbstractValueGenerator;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\Tax\TaxRuleType\TaxRuleTypeFilterInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Package('core')]
class AutoconfigureCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(EntityDefinition::class)
            ->addTag('cicada.entity.definition');

        $container
            ->registerForAutoconfiguration(SalesChannelDefinition::class)
            ->addTag('cicada.sales_channel.entity.definition');

        $container
            ->registerForAutoconfiguration(AbstractRouteScope::class)
            ->addTag('cicada.route_scope');

        $container
            ->registerForAutoconfiguration(EntityExtension::class)
            ->addTag('cicada.entity.extension');

        $container
            ->registerForAutoconfiguration(BulkEntityExtension::class)
            ->addTag('cicada.bulk.entity.extension');

        $container
            ->registerForAutoconfiguration(CartProcessorInterface::class)
            ->addTag('cicada.cart.processor');

        $container
            ->registerForAutoconfiguration(CartDataCollectorInterface::class)
            ->addTag('cicada.cart.collector');

        $container
            ->registerForAutoconfiguration(ScheduledTask::class)
            ->addTag('cicada.scheduled.task');

        $container
            ->registerForAutoconfiguration(CartValidatorInterface::class)
            ->addTag('cicada.cart.validator');

        $container
            ->registerForAutoconfiguration(LineItemFactoryInterface::class)
            ->addTag('cicada.cart.line_item.factory');

        $container
            ->registerForAutoconfiguration(LineItemGroupPackagerInterface::class)
            ->addTag('lineitem.group.packager');

        $container
            ->registerForAutoconfiguration(LineItemGroupSorterInterface::class)
            ->addTag('lineitem.group.sorter');

        $container
            ->registerForAutoconfiguration(LegacyEncoderInterface::class)
            ->addTag('cicada.legacy_encoder');

        $container
            ->registerForAutoconfiguration(EntityIndexer::class)
            ->addTag('cicada.entity_indexer');

        $container
            ->registerForAutoconfiguration(ExceptionHandlerInterface::class)
            ->addTag('cicada.dal.exception_handler');

        $container
            ->registerForAutoconfiguration(AbstractPaymentHandler::class)
            ->addTag('cicada.payment.method');

        $container
            ->registerForAutoconfiguration(FilterSorterInterface::class)
            ->addTag('promotion.filter.sorter');

        $container
            ->registerForAutoconfiguration(FilterPickerInterface::class)
            ->addTag('promotion.filter.picker');

        $container
            ->registerForAutoconfiguration(Rule::class)
            ->addTag('cicada.rule.definition');

        $container
            ->registerForAutoconfiguration(AbstractTaxProvider::class)
            ->addTag('cicada.tax.provider');

        $container
            ->registerForAutoconfiguration(CmsElementResolverInterface::class)
            ->addTag('cicada.cms.data_resolver');

        $container
            ->registerForAutoconfiguration(FieldSerializerInterface::class)
            ->addTag('cicada.field_serializer');

        $container
            ->registerForAutoconfiguration(FlowStorer::class)
            ->addTag('flow.storer');

        $container
            ->registerForAutoconfiguration(AbstractUrlProvider::class)
            ->addTag('cicada.sitemap_url_provider');

        $container
            ->registerForAutoconfiguration(AdapterFactoryInterface::class)
            ->addTag('cicada.filesystem.factory');

        $container
            ->registerForAutoconfiguration(AbstractValueGenerator::class)
            ->addTag('cicada.value_generator_pattern');

        $container
            ->registerForAutoconfiguration(TaxRuleTypeFilterInterface::class)
            ->addTag('tax.rule_type_filter');

        $container
            ->registerForAutoconfiguration(SeoUrlRouteInterface::class)
            ->addTag('cicada.seo_url.route');

        $container
            ->registerForAutoconfiguration(TemplateNamespaceHierarchyBuilderInterface::class)
            ->addTag('cicada.twig.hierarchy_builder');

        $container
            ->registerForAutoconfiguration(AbstractListingProcessor::class)
            ->addTag('cicada.listing.processor');

        $container
            ->registerForAutoconfiguration(AbstractListingFilterHandler::class)
            ->addTag('cicada.listing.filter.handler');

        $container->registerAliasForArgument('cicada.filesystem.private', FilesystemOperator::class, 'privateFilesystem');
        $container->registerAliasForArgument('cicada.filesystem.public', FilesystemOperator::class, 'publicFilesystem');
    }
}
