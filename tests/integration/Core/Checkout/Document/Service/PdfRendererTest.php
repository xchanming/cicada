<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Document\Service;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\DocumentConfiguration;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\DeliveryNoteRenderer;
use Cicada\Core\Checkout\Document\Renderer\DocumentRendererConfig;
use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Renderer\RenderedDocument;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Service\PdfRenderer;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Cicada\Tests\Integration\Core\Checkout\Document\DocumentTrait;

/**
 * @internal
 */
#[Package('checkout')]
class PdfRendererTest extends TestCase
{
    use DocumentTrait;

    private SalesChannelContext $salesChannelContext;

    private Context $context;

    private DeliveryNoteRenderer $deliveryNoteRenderer;

    private DocumentGenerator $documentGenerator;

    private PdfRenderer $pdfRenderer;

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
        $this->deliveryNoteRenderer = static::getContainer()->get(DeliveryNoteRenderer::class);
        $this->documentGenerator = static::getContainer()->get(DocumentGenerator::class);
        $this->pdfRenderer = static::getContainer()->get(PdfRenderer::class);
    }

    public function testRender(): void
    {
        // generates one line item for each tax
        $cart = $this->generateDemoCart(3);

        // generates credit items for each price
        $orderId = $this->persistCart($cart);

        $invoiceConfig = new DocumentConfiguration();
        $invoiceConfig->setDocumentNumber('1001');

        $operationInvoice = new DocumentGenerateOperation($orderId, FileTypes::PDF, $invoiceConfig->jsonSerialize());

        $invoice = $this->documentGenerator->generate(InvoiceRenderer::TYPE, [$orderId => $operationInvoice], $this->context)->getSuccess()->first();
        static::assertNotNull($invoice);
        $invoiceId = $invoice->getId();

        $operation = new DocumentGenerateOperation(
            $orderId,
            FileTypes::PDF,
            [
                'displayLineItems' => true,
                'itemsPerPage' => 10,
                'displayFooter' => true,
                'displayHeader' => true,
            ],
            $invoiceId
        );

        $processedTemplate = $this->deliveryNoteRenderer->render(
            [$orderId => $operation],
            $this->context,
            new DocumentRendererConfig()
        );

        static::assertArrayHasKey($orderId, $processedTemplate->getSuccess());
        static::assertInstanceOf(RenderedDocument::class, $processedTemplate->getSuccess()[$orderId]);

        $rendered = $processedTemplate->getSuccess()[$orderId];

        static::assertStringContainsString('<html>', $rendered->getHtml());
        static::assertStringContainsString('</html>', $rendered->getHtml());

        $generatorOutput = $this->pdfRenderer->render($rendered);
        static::assertNotEmpty($generatorOutput);

        $finfo = new \finfo(\FILEINFO_MIME_TYPE);
        static::assertEquals('application/pdf', $finfo->buffer($generatorOutput));
    }
}
