<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\EventListener;

use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\Service\ProductExportFileHandlerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('inventory')]
class ProductExportEventListener implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $productExportRepository,
        private readonly ProductExportFileHandlerInterface $productExportFileHandler,
        private readonly FilesystemOperator $fileSystem
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'product_export.written' => 'afterWrite',
        ];
    }

    public function afterWrite(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $writeResult) {
            if (!$this->productExportWritten($writeResult)) {
                continue;
            }

            $primaryKey = $writeResult->getPrimaryKey();
            $primaryKey = \is_array($primaryKey) ? $primaryKey['id'] : $primaryKey;

            $this->productExportRepository->update(
                [
                    [
                        'id' => $primaryKey,
                        'generatedAt' => null,
                    ],
                ],
                $event->getContext()
            );
            $productExportResult = $this->productExportRepository->search(new Criteria([$primaryKey]), $event->getContext());
            if ($productExportResult->getTotal() !== 0) {
                /** @var ProductExportEntity $productExport */
                $productExport = $productExportResult->first();

                $filePath = $this->productExportFileHandler->getFilePath($productExport);
                if ($this->fileSystem->fileExists($filePath)) {
                    $this->fileSystem->delete($filePath);
                }
            }
        }
    }

    private function productExportWritten(EntityWriteResult $writeResult): bool
    {
        return $writeResult->getEntityName() === 'product_export'
            && $writeResult->getOperation() !== EntityWriteResult::OPERATION_DELETE
            && !\array_key_exists('generatedAt', $writeResult->getPayload());
    }
}
