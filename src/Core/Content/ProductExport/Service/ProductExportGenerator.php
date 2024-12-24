<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Service;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\ProductExport\Event\ProductExportChangeEncodingEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportLoggingEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportProductCriteriaEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportRenderBodyContextEvent;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\ProductExportException;
use Cicada\Core\Content\ProductExport\Struct\ExportBehavior;
use Cicada\Core\Content\ProductExport\Struct\ProductExportResult;
use Cicada\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Cicada\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\Adapter\Twig\TwigVariableParser;
use Cicada\Core\Framework\Adapter\Twig\TwigVariableParserFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\SalesChannelRepositoryIterator;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Doctrine\DBAL\Connection;
use Monolog\Level;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

#[Package('inventory')]
class ProductExportGenerator implements ProductExportGeneratorInterface
{
    private readonly TwigVariableParser $twigVariableParser;

    /**
     * @internal
     *
     * @param SalesChannelRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private readonly ProductStreamBuilderInterface $productStreamBuilder,
        private readonly SalesChannelRepository $productRepository,
        private readonly ProductExportRendererInterface $productExportRender,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ProductExportValidatorInterface $productExportValidator,
        private readonly SalesChannelContextServiceInterface $salesChannelContextService,
        private readonly AbstractTranslator $translator,
        private readonly SalesChannelContextPersister $contextPersister,
        private readonly Connection $connection,
        private readonly int $readBufferSize,
        private readonly SeoUrlPlaceholderHandlerInterface $seoUrlPlaceholderHandler,
        Environment $twig,
        private readonly ProductDefinition $productDefinition,
        private readonly LanguageLocaleCodeProvider $languageLocaleProvider,
        TwigVariableParserFactory $parserFactory
    ) {
        $this->twigVariableParser = $parserFactory->getParser($twig);
    }

    public function generate(ProductExportEntity $productExport, ExportBehavior $exportBehavior): ?ProductExportResult
    {
        $contextToken = Uuid::randomHex();
        $this->contextPersister->save(
            $contextToken,
            [
                SalesChannelContextService::CURRENCY_ID => $productExport->getCurrencyId(),
            ],
            $productExport->getSalesChannelId()
        );

        $context = $this->salesChannelContextService->get(
            new SalesChannelContextServiceParameters(
                $productExport->getStorefrontSalesChannelId(),
                $contextToken,
                $productExport->getSalesChannelDomain()->getLanguageId(),
                $productExport->getCurrencyId()
            )
        );

        $this->translator->injectSettings(
            $productExport->getStorefrontSalesChannelId(),
            $productExport->getSalesChannelDomain()->getLanguageId(),
            $this->languageLocaleProvider->getLocaleForLanguageId($productExport->getSalesChannelDomain()->getLanguageId()),
            $context->getContext()
        );

        $filters = $this->productStreamBuilder->buildFilters(
            $productExport->getProductStreamId(),
            $context->getContext()
        );

        $associations = $this->getAssociations($productExport, $context);

        $criteria = new Criteria();
        $criteria
            ->setTitle('product-export::products')
            ->addFilter(...$filters)
            ->setOffset($exportBehavior->offset())
            ->setLimit($this->readBufferSize);

        foreach ($associations as $association) {
            $criteria->addAssociation($association);
        }

        $this->eventDispatcher->dispatch(
            new ProductExportProductCriteriaEvent($criteria, $productExport, $exportBehavior, $context)
        );

        $iterator = new SalesChannelRepositoryIterator($this->productRepository, $context, $criteria);

        $total = $iterator->getTotal();
        if ($total === 0) {
            $exception = ProductExportException::productExportNotFound($productExport->getId());

            $loggingEvent = new ProductExportLoggingEvent(
                $context->getContext(),
                $exception->getMessage(),
                Level::Warning,
                $exception
            );

            $this->eventDispatcher->dispatch($loggingEvent);

            $this->translator->resetInjection();
            $this->connection->delete('sales_channel_api_context', ['token' => $contextToken]);

            throw $exception;
        }

        $content = '';
        if ($exportBehavior->generateHeader()) {
            $content = $this->productExportRender->renderHeader($productExport, $context);
        }

        $productContext = $this->eventDispatcher->dispatch(
            new ProductExportRenderBodyContextEvent(
                [
                    'productExport' => $productExport,
                    'context' => $context,
                ]
            )
        );

        $body = '';
        while ($productResult = $iterator->fetch()) {
            foreach ($productResult->getEntities() as $product) {
                $data = $productContext->getContext();
                $data['product'] = $product;

                if ($productExport->isIncludeVariants() && !$product->getParentId() && $product->getChildCount() > 0) {
                    continue; // Skip main product if variants are included
                }
                if (!$productExport->isIncludeVariants() && $product->getParentId()) {
                    continue; // Skip variants unless they are included
                }

                $body .= $this->productExportRender->renderBody($productExport, $context, $data);
            }

            if ($exportBehavior->batchMode()) {
                break;
            }
        }
        $content .= $this->seoUrlPlaceholderHandler->replace($body, $productExport->getSalesChannelDomain()->getUrl(), $context);

        if ($exportBehavior->generateFooter()) {
            $content .= $this->productExportRender->renderFooter($productExport, $context);
        }

        $encodingEvent = $this->eventDispatcher->dispatch(
            new ProductExportChangeEncodingEvent($productExport, $content, mb_convert_encoding($content, $productExport->getEncoding()))
        );

        $this->translator->resetInjection();

        $this->connection->delete('sales_channel_api_context', ['token' => $contextToken]);

        if (empty($content)) {
            return null;
        }

        return new ProductExportResult(
            $encodingEvent->getEncodedContent(),
            $this->productExportValidator->validate($productExport, $encodingEvent->getEncodedContent()),
            $iterator->getTotal()
        );
    }

    /**
     * @return array<string>
     */
    private function getAssociations(ProductExportEntity $productExport, SalesChannelContext $context): array
    {
        try {
            $variables = $this->twigVariableParser->parse((string) $productExport->getBodyTemplate());
        } catch (\Exception $e) {
            $e = ProductExportException::renderProductException($e->getMessage());

            $loggingEvent = new ProductExportLoggingEvent($context->getContext(), $e->getMessage(), Level::Warning, $e);

            $this->eventDispatcher->dispatch($loggingEvent);

            throw $e;
        }

        $associations = [];
        foreach ($variables as $variable) {
            $associations[] = EntityDefinitionQueryHelper::getAssociationPath($variable, $this->productDefinition);
        }

        return array_filter(array_unique($associations));
    }
}
