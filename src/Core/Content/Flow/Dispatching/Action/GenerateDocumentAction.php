<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Action;

use Psr\Log\LoggerInterface;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Content\Flow\Dispatching\DelayableAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('services-settings')]
class GenerateDocumentAction extends FlowAction implements DelayableAction
{
    /**
     * @internal
     */
    public function __construct(
        private readonly DocumentGenerator $documentGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getName(): string
    {
        return 'action.generate.document';
    }

    /**
     * @return array<int, string>
     */
    public function requirements(): array
    {
        return [OrderAware::class];
    }

    public function handleFlow(StorableFlow $flow): void
    {
        if (!$flow->hasData(OrderAware::ORDER_ID)) {
            return;
        }

        $this->generate($flow->getContext(), $flow->getConfig(), $flow->getData(OrderAware::ORDER_ID));
    }

    /**
     * @param array<string, mixed> $eventConfig
     */
    private function generate(Context $context, array $eventConfig, string $orderId): void
    {
        if (\array_key_exists('documentType', $eventConfig)) {
            $this->generateDocument($eventConfig, $context, $orderId);

            return;
        }

        $documentsConfig = $eventConfig['documentTypes'];

        if (!$documentsConfig) {
            return;
        }

        // Invoice document should be created first
        foreach ($documentsConfig as $index => $config) {
            if ($config['documentType'] === InvoiceRenderer::TYPE) {
                $this->generateDocument($config, $context, $orderId);
                unset($documentsConfig[$index]);

                break;
            }
        }

        foreach ($documentsConfig as $config) {
            $this->generateDocument($config, $context, $orderId);
        }
    }

    /**
     * @param array<string, mixed> $eventConfig
     */
    private function generateDocument(array $eventConfig, Context $context, string $orderId): void
    {
        $documentType = $eventConfig['documentType'];
        $documentRangerType = $eventConfig['documentRangerType'];

        if (!$documentType || !$documentRangerType) {
            return;
        }

        $fileType = $eventConfig['fileType'] ?? FileTypes::PDF;
        $config = $eventConfig['config'] ?? [];
        $static = $eventConfig['static'] ?? false;

        $operation = new DocumentGenerateOperation($orderId, $fileType, $config, null, $static);

        $result = $this->documentGenerator->generate($documentType, [$orderId => $operation], $context);

        if (!empty($result->getErrors())) {
            foreach ($result->getErrors() as $error) {
                $this->logger->error($error->getMessage());
            }
        }
    }
}
