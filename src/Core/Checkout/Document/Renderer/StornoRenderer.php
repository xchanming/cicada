<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Renderer;

use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Document\DocumentException;
use Cicada\Core\Checkout\Document\Event\StornoOrdersEvent;
use Cicada\Core\Checkout\Document\Service\DocumentConfigLoader;
use Cicada\Core\Checkout\Document\Service\ReferenceInvoiceLoader;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\Locale\LocaleEntity;
use Cicada\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('checkout')]
final class StornoRenderer extends AbstractDocumentRenderer
{
    public const TYPE = 'storno';

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly DocumentConfigLoader $documentConfigLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DocumentTemplateRenderer $documentTemplateRenderer,
        private readonly NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        private readonly ReferenceInvoiceLoader $referenceInvoiceLoader,
        private readonly string $rootDir,
        private readonly Connection $connection
    ) {
    }

    public function supports(): string
    {
        return self::TYPE;
    }

    public function render(array $operations, Context $context, DocumentRendererConfig $rendererConfig): RendererResult
    {
        $result = new RendererResult();

        $template = '@Framework/documents/storno.html.twig';

        $ids = \array_map(fn (DocumentGenerateOperation $operation) => $operation->getOrderId(), $operations);

        if (empty($ids)) {
            return $result;
        }

        $referenceInvoiceNumbers = [];

        $orders = new OrderCollection();

        /** @var DocumentGenerateOperation $operation */
        foreach ($operations as $operation) {
            try {
                $orderId = $operation->getOrderId();
                $invoice = $this->referenceInvoiceLoader->load($orderId, $operation->getReferencedDocumentId(), $rendererConfig->deepLinkCode);

                if (empty($invoice)) {
                    throw DocumentException::generationError('Can not generate storno document because no invoice document exists. OrderId: ' . $operation->getOrderId());
                }

                $documentRefer = json_decode($invoice['config'], true, 512, \JSON_THROW_ON_ERROR);
                $referenceInvoiceNumbers[$orderId] = $invoice['documentNumber'] ?? $documentRefer['documentNumber'];

                $order = $this->getOrder($orderId, $invoice['orderVersionId'], $context, $rendererConfig->deepLinkCode);

                $orders->add($order);
                $operation->setReferencedDocumentId($invoice['id']);
                if ($order->getVersionId()) {
                    $operation->setOrderVersionId($order->getVersionId());
                }
            } catch (\Throwable $exception) {
                $result->addError($operation->getOrderId(), $exception);
            }
        }

        // TODO: future implementation (only fetch required data and associations)

        $this->eventDispatcher->dispatch(new StornoOrdersEvent($orders, $context, $operations));

        foreach ($orders as $order) {
            $orderId = $order->getId();

            try {
                $operation = $operations[$orderId] ?? null;

                if ($operation === null) {
                    continue;
                }

                $forceDocumentCreation = $operation->getConfig()['forceDocumentCreation'] ?? true;
                if (!$forceDocumentCreation && $order->getDocuments()?->first()) {
                    continue;
                }

                $order = $this->handlePrices($order);

                $config = clone $this->documentConfigLoader->load(self::TYPE, $order->getSalesChannelId(), $context);

                $config->merge($operation->getConfig());

                $number = $config->getDocumentNumber() ?: $this->getNumber($context, $order, $operation);

                $referenceDocumentNumber = $referenceInvoiceNumbers[$operation->getOrderId()];

                $now = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

                $config->merge([
                    'documentDate' => $operation->getConfig()['documentDate'] ?? $now,
                    'documentNumber' => $number,
                    'custom' => [
                        'stornoNumber' => $number,
                        'invoiceNumber' => $referenceDocumentNumber,
                    ],
                    'intraCommunityDelivery' => $this->isAllowIntraCommunityDelivery(
                        $config->jsonSerialize(),
                        $order,
                    ),
                ]);

                if ($operation->isStatic()) {
                    $doc = new RenderedDocument('', $number, $config->buildName(), $operation->getFileType(), $config->jsonSerialize());
                    $result->addSuccess($orderId, $doc);

                    continue;
                }

                /** @var LanguageEntity|null $language */
                $language = $order->getLanguage();
                if ($language === null) {
                    throw DocumentException::generationError('Can not generate credit note document because no language exists. OrderId: ' . $operation->getOrderId());
                }

                /** @var LocaleEntity $locale */
                $locale = $language->getLocale();

                $html = $this->documentTemplateRenderer->render(
                    $template,
                    [
                        'order' => $order,
                        'config' => $config,
                        'rootDir' => $this->rootDir,
                        'context' => $context,
                    ],
                    $context,
                    $order->getSalesChannelId(),
                    $order->getLanguageId(),
                    $locale->getCode()
                );

                $doc = new RenderedDocument(
                    $html,
                    $number,
                    $config->buildName(),
                    $operation->getFileType(),
                    $config->jsonSerialize(),
                );

                $result->addSuccess($orderId, $doc);
            } catch (\Throwable $exception) {
                $result->addError($orderId, $exception);
            }
        }

        return $result;
    }

    public function getDecorated(): AbstractDocumentRenderer
    {
        throw new DecorationPatternException(self::class);
    }

    private function getOrder(string $orderId, string $versionId, Context $context, string $deepLinkCode = ''): OrderEntity
    {
        ['language_id' => $languageId] = $this->getOrdersLanguageId([$orderId], $versionId, $this->connection)[0];

        // Get the correct order with versioning from reference invoice
        $versionContext = $context->createWithVersionId($versionId)->assign([
            'languageIdChain' => array_values(array_unique(array_filter([$languageId, ...$context->getLanguageIdChain()]))),
        ]);

        $criteria = OrderDocumentCriteriaFactory::create([$orderId], $deepLinkCode, self::TYPE);

        /** @var ?OrderEntity $order */
        $order = $this->orderRepository->search($criteria, $versionContext)->get($orderId);

        if ($order === null) {
            throw DocumentException::orderNotFound($orderId);
        }

        return $order;
    }

    private function handlePrices(OrderEntity $order): OrderEntity
    {
        foreach ($order->getLineItems() ?? [] as $lineItem) {
            $lineItem->setUnitPrice($lineItem->getUnitPrice() / -1);
            $lineItem->setTotalPrice($lineItem->getTotalPrice() / -1);
        }

        foreach ($order->getPrice()->getCalculatedTaxes()->sortByTax()->getElements() as $tax) {
            $tax->setTax($tax->getTax() / -1);
        }

        $order->setShippingTotal($order->getShippingTotal() / -1);
        $order->setAmountNet($order->getAmountNet() / -1);
        $order->setAmountTotal($order->getAmountTotal() / -1);

        $currentOrderCartPrice = $order->getPrice();
        $CartPrice = new CartPrice(
            $currentOrderCartPrice->getNetPrice(),
            $currentOrderCartPrice->getTotalPrice() / -1,
            $currentOrderCartPrice->getPositionPrice(),
            $currentOrderCartPrice->getCalculatedTaxes(),
            $currentOrderCartPrice->getTaxRules(),
            $currentOrderCartPrice->getTaxStatus(),
            $currentOrderCartPrice->getRawTotal(),
        );
        $order->setPrice($CartPrice);

        return $order;
    }

    private function getNumber(Context $context, OrderEntity $order, DocumentGenerateOperation $operation): string
    {
        return $this->numberRangeValueGenerator->getValue(
            'document_' . self::TYPE,
            $context,
            $order->getSalesChannelId(),
            $operation->isPreview()
        );
    }
}
