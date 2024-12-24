<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Renderer;

use Doctrine\DBAL\Connection;
use Cicada\Core\Checkout\Document\DocumentException;
use Cicada\Core\Checkout\Document\Event\InvoiceOrdersEvent;
use Cicada\Core\Checkout\Document\Service\DocumentConfigLoader;
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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('checkout')]
final class InvoiceRenderer extends AbstractDocumentRenderer
{
    public const TYPE = 'invoice';

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly DocumentConfigLoader $documentConfigLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DocumentTemplateRenderer $documentTemplateRenderer,
        private readonly NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
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

        $template = '@Framework/documents/invoice.html.twig';

        $ids = \array_map(fn (DocumentGenerateOperation $operation) => $operation->getOrderId(), $operations);

        if (empty($ids)) {
            return $result;
        }

        $languageIdChain = $context->getLanguageIdChain();

        $chunk = $this->getOrdersLanguageId(array_values($ids), $context->getVersionId(), $this->connection);

        foreach ($chunk as ['language_id' => $languageId, 'ids' => $ids]) {
            $criteria = OrderDocumentCriteriaFactory::create(explode(',', (string) $ids), $rendererConfig->deepLinkCode, self::TYPE);

            $context = $context->assign([
                'languageIdChain' => array_values(array_unique(array_filter([$languageId, ...$languageIdChain]))),
            ]);

            // TODO: future implementation (only fetch required data and associations)

            /** @var OrderCollection $orders */
            $orders = $this->orderRepository->search($criteria, $context)->getEntities();

            $this->eventDispatcher->dispatch(new InvoiceOrdersEvent($orders, $context, $operations));

            foreach ($orders as $order) {
                $orderId = $order->getId();

                try {
                    if (!\array_key_exists($orderId, $operations)) {
                        continue;
                    }

                    /** @var DocumentGenerateOperation $operation */
                    $operation = $operations[$orderId];

                    $forceDocumentCreation = $operation->getConfig()['forceDocumentCreation'] ?? true;
                    if (!$forceDocumentCreation && $order->getDocuments()?->first()) {
                        continue;
                    }

                    $config = clone $this->documentConfigLoader->load(self::TYPE, $order->getSalesChannelId(), $context);

                    $config->merge($operation->getConfig());

                    $number = $config->getDocumentNumber() ?: $this->getNumber($context, $order, $operation);

                    $now = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

                    $config->merge([
                        'documentDate' => $operation->getConfig()['documentDate'] ?? $now,
                        'documentNumber' => $number,
                        'intraCommunityDelivery' => $this->isAllowIntraCommunityDelivery(
                            $config->jsonSerialize(),
                            $order,
                        ),
                        'custom' => [
                            'invoiceNumber' => $number,
                        ],
                    ]);

                    // create version of order to ensure the document stays the same even if the order changes
                    $operation->setOrderVersionId($this->orderRepository->createVersion($orderId, $context, 'document'));

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
        }

        return $result;
    }

    public function getDecorated(): AbstractDocumentRenderer
    {
        throw new DecorationPatternException(self::class);
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
