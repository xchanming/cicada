<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\SalesChannel;

use Cicada\Core\Content\ProductExport\Event\ProductExportContentTypeEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportLoggingEvent;
use Cicada\Core\Content\ProductExport\Exception\ExportNotFoundException;
use Cicada\Core\Content\ProductExport\Exception\ExportNotGeneratedException;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\Service\ProductExporterInterface;
use Cicada\Core\Content\ProductExport\Service\ProductExportFileHandlerInterface;
use Cicada\Core\Content\ProductExport\Struct\ExportBehavior;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use League\Flysystem\FilesystemOperator;
use Monolog\Level;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('inventory')]
class ExportController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ProductExporterInterface $productExportService,
        private readonly ProductExportFileHandlerInterface $productExportFileHandler,
        private readonly FilesystemOperator $fileSystem,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityRepository $productExportRepository,
        private readonly AbstractSalesChannelContextFactory $contextFactory
    ) {
    }

    #[Route(path: '/store-api/product-export/{accessKey}/{fileName}', name: 'store-api.product.export', methods: ['GET'], defaults: ['auth_required' => false])]
    public function index(Request $request): Response
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('fileName', $request->get('fileName')))
            ->addFilter(new EqualsFilter('accessKey', $request->get('accessKey')))
            ->addFilter(new EqualsFilter('salesChannel.active', true))
            ->addAssociation('salesChannelDomain');

        /** @var ProductExportEntity|null $productExport */
        $productExport = $this->productExportRepository->search($criteria, $context)->first();

        if ($productExport === null) {
            $exportNotFoundException = new ExportNotFoundException(null, $request->get('fileName'));
            $this->logException($context, $exportNotFoundException, Level::Warning);

            throw $exportNotFoundException;
        }

        $context = $this->contextFactory->create('', $productExport->getSalesChannelDomain()->getSalesChannelId());

        $filePath = $this->productExportFileHandler->getFilePath($productExport);

        // if file not present or interval = live
        if (!$this->fileSystem->fileExists($filePath) || $productExport->getInterval() === 0) {
            $this->productExportService->export($context, new ExportBehavior(), $productExport->getId());
        }

        if (!$this->fileSystem->fileExists($filePath)) {
            $exportNotGeneratedException = new ExportNotGeneratedException();
            $this->logException($context->getContext(), $exportNotGeneratedException);

            throw $exportNotGeneratedException;
        }

        $content = $this->fileSystem->read($filePath);
        $contentType = $this->getContentType($productExport->getFileFormat());
        $encoding = $productExport->getEncoding();

        $response = new Response($content ?: null, 200, ['Content-Type' => $contentType . ';charset=' . $encoding]);
        $response->setLastModified((new \DateTimeImmutable())->setTimestamp($this->fileSystem->lastModified($filePath)));
        $response->setCharset($encoding);

        return $response;
    }

    private function getContentType(string $fileFormat): string
    {
        $contentType = 'text/plain';

        switch ($fileFormat) {
            case ProductExportEntity::FILE_FORMAT_CSV:
                $contentType = 'text/csv';

                break;
            case ProductExportEntity::FILE_FORMAT_XML:
                $contentType = 'text/xml';

                break;
        }

        $event = new ProductExportContentTypeEvent($fileFormat, $contentType);
        $this->eventDispatcher->dispatch($event);

        return $event->getContentType();
    }

    private function logException(
        Context $context,
        \Exception $exception,
        Level $logLevel = Level::Error
    ): void {
        $loggingEvent = new ProductExportLoggingEvent(
            $context,
            $exception->getMessage(),
            $logLevel,
            $exception
        );

        $this->eventDispatcher->dispatch($loggingEvent);
    }
}
