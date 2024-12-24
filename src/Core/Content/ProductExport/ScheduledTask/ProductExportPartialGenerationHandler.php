<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\ScheduledTask;

use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\Service\ProductExportFileHandlerInterface;
use Cicada\Core\Content\ProductExport\Service\ProductExportGeneratorInterface;
use Cicada\Core\Content\ProductExport\Service\ProductExportRendererInterface;
use Cicada\Core\Content\ProductExport\Struct\ExportBehavior;
use Cicada\Core\Content\ProductExport\Struct\ProductExportResult;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\Exception\SalesChannelNotFoundException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('inventory')]
final class ProductExportPartialGenerationHandler
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ProductExportGeneratorInterface $productExportGenerator,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly EntityRepository $productExportRepository,
        private readonly ProductExportFileHandlerInterface $productExportFileHandler,
        private readonly MessageBusInterface $messageBus,
        private readonly ProductExportRendererInterface $productExportRender,
        private readonly AbstractTranslator $translator,
        private readonly SalesChannelContextServiceInterface $salesChannelContextService,
        private readonly SalesChannelContextPersister $contextPersister,
        private readonly Connection $connection,
        private readonly int $readBufferSize,
        private readonly LanguageLocaleCodeProvider $languageLocaleProvider
    ) {
    }

    public function __invoke(ProductExportPartialGeneration $productExportPartialGeneration): void
    {
        $context = $this->getContext($productExportPartialGeneration);
        $productExport = $this->fetchProductExport($productExportPartialGeneration, $context);

        if (!$productExport) {
            return;
        }

        $exportResult = $this->runExport($productExport, $productExportPartialGeneration->getOffset(), $context);

        $filePath = $this->productExportFileHandler->getFilePath($productExport, true);

        if ($exportResult === null) {
            $this->finalizeExport($productExport, $filePath);

            return;
        }

        $this->productExportFileHandler->writeProductExportContent(
            $exportResult->getContent(),
            $filePath,
            $productExportPartialGeneration->getOffset() > 0
        );

        if ($productExportPartialGeneration->getOffset() + $this->readBufferSize < $exportResult->getTotal()) {
            $this->messageBus->dispatch(
                new ProductExportPartialGeneration(
                    $productExportPartialGeneration->getProductExportId(),
                    $productExportPartialGeneration->getSalesChannelId(),
                    $productExportPartialGeneration->getOffset() + $this->readBufferSize
                )
            );

            return;
        }

        $this->finalizeExport($productExport, $filePath);
    }

    private function getContext(ProductExportPartialGeneration $productExportPartialGeneration): Context
    {
        $context = $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            $productExportPartialGeneration->getSalesChannelId()
        );

        if ($context->getSalesChannel()->getTypeId() !== Defaults::SALES_CHANNEL_TYPE_STOREFRONT) {
            throw new SalesChannelNotFoundException();
        }

        return $context->getContext();
    }

    private function fetchProductExport(
        ProductExportPartialGeneration $productExportPartialGeneration,
        Context $context
    ): ?ProductExportEntity {
        $criteria = new Criteria([$productExportPartialGeneration->getProductExportId()]);
        $criteria
            ->addAssociation('salesChannel')
            ->addAssociation('salesChannelDomain.salesChannel')
            ->addAssociation('salesChannelDomain.language.locale')
            ->addAssociation('productStream.filters.queries')
            ->setLimit(1);

        /** @var ProductExportEntity|null $productExport */
        $productExport = $this->productExportRepository
            ->search($criteria, $context)
            ->first();

        return $productExport;
    }

    private function runExport(
        ProductExportEntity $productExport,
        int $offset,
        Context $context
    ): ?ProductExportResult {
        $this->productExportRepository->update([[
            'id' => $productExport->getId(),
            'isRunning' => true,
        ]], $context);

        return $this->productExportGenerator->generate(
            $productExport,
            new ExportBehavior(
                false,
                false,
                true,
                false,
                false,
                $offset
            )
        );
    }

    private function finalizeExport(ProductExportEntity $productExport, string $filePath): void
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
                $productExport->getSalesChannelDomain()->getCurrencyId() ?? $productExport->getCurrencyId()
            )
        );

        $this->translator->injectSettings(
            $productExport->getStorefrontSalesChannelId(),
            $productExport->getSalesChannelDomain()->getLanguageId(),
            $this->languageLocaleProvider->getLocaleForLanguageId($productExport->getSalesChannelDomain()->getLanguageId()),
            $context->getContext()
        );

        $headerContent = $this->productExportRender->renderHeader($productExport, $context);
        $footerContent = $this->productExportRender->renderFooter($productExport, $context);
        $finalFilePath = $this->productExportFileHandler->getFilePath($productExport);

        $this->translator->resetInjection();

        $writeProductExportSuccessful = $this->productExportFileHandler->finalizePartialProductExport(
            $filePath,
            $finalFilePath,
            $headerContent,
            $footerContent
        );

        $this->connection->delete('sales_channel_api_context', ['token' => $contextToken]);

        if (!$writeProductExportSuccessful) {
            return;
        }

        $this->productExportRepository->update(
            [
                [
                    'id' => $productExport->getId(),
                    'generatedAt' => new \DateTime(),
                    'isRunning' => false,
                ],
            ],
            $context->getContext()
        );
    }
}
