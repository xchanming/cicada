<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Document\Renderer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Cicada\Core\Checkout\Cart\PriceDefinitionFactory;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Document\DocumentConfiguration;
use Cicada\Core\Checkout\Document\DocumentException;
use Cicada\Core\Checkout\Document\Event\StornoOrdersEvent;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\DocumentRendererConfig;
use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Renderer\RenderedDocument;
use Cicada\Core\Checkout\Document\Renderer\StornoRenderer;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\Currency\CurrencyFormatter;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Cicada\Tests\Integration\Core\Checkout\Document\DocumentTrait;

/**
 * @internal
 */
#[Package('checkout')]
class StornoRendererTest extends TestCase
{
    use DocumentTrait;

    private SalesChannelContext $salesChannelContext;

    private Context $context;

    private EntityRepository $productRepository;

    private StornoRenderer $stornoRenderer;

    private CartService $cartService;

    private DocumentGenerator $documentGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Context::createDefaultContext();

        $priceRuleId = Uuid::randomHex();

        $this->salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL,
            [
                SalesChannelContextService::CUSTOMER_ID => $this->createCustomer(),
            ]
        );

        $this->salesChannelContext->setRuleIds([$priceRuleId]);
        $this->productRepository = static::getContainer()->get('product.repository');
        $this->stornoRenderer = static::getContainer()->get(StornoRenderer::class);
        $this->cartService = static::getContainer()->get(CartService::class);
        $this->documentGenerator = static::getContainer()->get(DocumentGenerator::class);
    }

    /**
     * @param array<string, string> $additionalConfig
     */
    #[DataProvider('stornoNoteRendererDataProvider')]
    public function testRender(array $additionalConfig, \Closure $assertionCallback): void
    {
        $cart = $this->generateDemoCart([7, 31]);
        $orderId = $this->cartService->order($cart, $this->salesChannelContext, new RequestDataBag());

        $invoiceConfig = new DocumentConfiguration();
        $invoiceConfig->setDocumentNumber('1001');

        $operationInvoice = new DocumentGenerateOperation($orderId, FileTypes::PDF, $invoiceConfig->jsonSerialize());

        $result = $this->documentGenerator->generate(InvoiceRenderer::TYPE, [$orderId => $operationInvoice], $this->context)->getSuccess()->first();
        static::assertNotNull($result);
        $invoiceId = $result->getId();

        $config = [
            'displayLineItems' => true,
            'itemsPerPage' => 10,
            'displayFooter' => true,
            'displayHeader' => true,
        ];

        if (!empty($additionalConfig)) {
            $config = array_merge($config, $additionalConfig);
        }

        $operation = new DocumentGenerateOperation(
            $orderId,
            FileTypes::PDF,
            $config,
            $invoiceId
        );

        $caughtEvent = null;

        static::getContainer()->get('event_dispatcher')
            ->addListener(StornoOrdersEvent::class, function (StornoOrdersEvent $event) use (&$caughtEvent): void {
                $caughtEvent = $event;
            });

        $processedTemplate = $this->stornoRenderer->render(
            [$orderId => $operation],
            $this->context,
            new DocumentRendererConfig()
        );

        static::assertInstanceOf(StornoOrdersEvent::class, $caughtEvent);
        static::assertCount(1, $caughtEvent->getOperations());
        static::assertSame($operation, $caughtEvent->getOperations()[$orderId] ?? null);
        static::assertCount(1, $caughtEvent->getOrders());
        $order = $caughtEvent->getOrders()->get($orderId);
        static::assertNotNull($order);
        static::assertArrayHasKey($orderId, $processedTemplate->getSuccess());
        $rendered = $processedTemplate->getSuccess()[$orderId];
        static::assertInstanceOf(RenderedDocument::class, $rendered);
        static::assertStringContainsString('<html>', $rendered->getHtml());
        static::assertStringContainsString('</html>', $rendered->getHtml());

        $localeProvider = static::createMock(LanguageLocaleCodeProvider::class);
        $formatter = new CurrencyFormatter($localeProvider);
        $orderCurrency = $order->getCurrency();
        if ($orderCurrency !== null) {
            $orderAmounts = [
                $order->getAmountNet(),
                $order->getPrice()->getRawTotal(),
                $order->getPrice()->getTotalPrice(),
            ];
            $orderIsoCode = $orderCurrency->getIsoCode();
            foreach ($orderAmounts as $amount) {
                $formattedValue = $formatter->formatCurrencyByLanguage(
                    $amount,
                    $orderIsoCode,
                    $this->context->getLanguageId(),
                    $this->context,
                );
                static::assertStringContainsString($formattedValue, $rendered->getHtml());
            }
        }
        $assertionCallback($rendered);
    }

    public function testRenderWithoutInvoice(): void
    {
        $cart = $this->generateDemoCart([7, 13]);
        $orderId = $this->cartService->order($cart, $this->salesChannelContext, new RequestDataBag());

        $operation = new DocumentGenerateOperation($orderId);

        $processedTemplate = $this->stornoRenderer->render(
            [$orderId => $operation],
            $this->context,
            new DocumentRendererConfig()
        );

        static::assertEmpty($processedTemplate->getSuccess());
        static::assertNotEmpty($errors = $processedTemplate->getErrors());
        static::assertArrayHasKey($orderId, $errors);
        static::assertInstanceOf(DocumentException::class, $errors[$orderId]);
        static::assertEquals(
            "Unable to generate document. Can not generate storno document because no invoice document exists. OrderId: $orderId",
            $errors[$orderId]->getMessage()
        );
    }

    public static function stornoNoteRendererDataProvider(): \Generator
    {
        yield 'render storno successfully' => [
            [
                'documentNumber' => '1000',
                'custom' => [
                    'stornoNumber' => '1000',
                    'invoiceNumber' => '1001',
                ],
            ],
            function (?RenderedDocument $rendered = null): void {
                static::assertNotNull($rendered);
                static::assertStringContainsString('Cancellation no. 1000', $rendered->getHtml());
                static::assertStringContainsString('Cancellation 1000 for Invoice 1001', $rendered->getHtml());
            },
        ];

        yield 'render storno with document number' => [
            [
                'documentNumber' => 'STORNO_9999',
            ],
            function (?RenderedDocument $rendered = null): void {
                static::assertNotNull($rendered);
                static::assertEquals('STORNO_9999', $rendered->getNumber());
                static::assertEquals('cancellation_invoice_STORNO_9999', $rendered->getName());
            },
        ];
    }

    public function testUsingTheSameOrderVersionIdWithReferenceDocument(): void
    {
        $cart = $this->generateDemoCart([7]);
        $orderId = $this->persistCart($cart);

        $invoiceConfig = new DocumentConfiguration();
        $invoiceConfig->setDocumentNumber('1001');

        $operationInvoice = new DocumentGenerateOperation($orderId, FileTypes::PDF, $invoiceConfig->jsonSerialize());

        $result = $this->documentGenerator->generate(InvoiceRenderer::TYPE, [$orderId => $operationInvoice], $this->context)->getSuccess()->first();
        static::assertNotNull($result);

        $operationStorno = new DocumentGenerateOperation($orderId);

        static::assertEquals($operationStorno->getOrderVersionId(), Defaults::LIVE_VERSION);
        static::assertTrue($this->orderVersionExists($orderId, $operationStorno->getOrderVersionId()));

        $this->stornoRenderer->render(
            [$orderId => $operationStorno],
            $this->context,
            new DocumentRendererConfig()
        );

        static::assertEquals($operationInvoice->getOrderVersionId(), $operationStorno->getOrderVersionId());
        static::assertTrue($this->orderVersionExists($orderId, $operationStorno->getOrderVersionId()));
    }

    /**
     * @param array<int, int> $taxes
     */
    private function generateDemoCart(array $taxes): Cart
    {
        $cart = $this->cartService->createNew('A');

        $keywords = ['awesome', 'epic', 'high quality'];

        $products = [];

        $factory = new ProductLineItemFactory(new PriceDefinitionFactory());

        $ids = new IdsCollection();

        $lineItems = [];

        foreach ($taxes as $tax) {
            $price = random_int(100, 200000) / 100.0;

            shuffle($keywords);
            $name = ucfirst(implode(' ', $keywords) . ' product');

            $number = Uuid::randomHex();

            $product = (new ProductBuilder($ids, $number))
                ->price($price)
                ->name($name)
                ->active(true)
                ->tax('test-' . Uuid::randomHex(), $tax)
                ->visibility()
                ->build();

            $products[] = $product;

            $lineItems[] = $factory->create(['id' => $ids->get($number), 'referencedId' => $ids->get($number)], $this->salesChannelContext);
            $this->addTaxDataToSalesChannel($this->salesChannelContext, $product['tax']);
        }

        $this->productRepository->create($products, Context::createDefaultContext());

        return $this->cartService->add($cart, $lineItems, $this->salesChannelContext);
    }
}
