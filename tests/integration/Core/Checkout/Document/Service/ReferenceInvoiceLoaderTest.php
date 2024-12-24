<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Document\Service;

use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Service\ReferenceInvoiceLoader;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Cicada\Tests\Integration\Core\Checkout\Document\DocumentTrait;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class ReferenceInvoiceLoaderTest extends TestCase
{
    use DocumentTrait;

    private ReferenceInvoiceLoader $referenceInvoiceLoader;

    private Context $context;

    private SalesChannelContext $salesChannelContext;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referenceInvoiceLoader = static::getContainer()->get(ReferenceInvoiceLoader::class);
        $this->context = Context::createDefaultContext();
        $customerId = $this->createCustomer();

        $this->salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL,
            [
                SalesChannelContextService::CUSTOMER_ID => $customerId,
            ]
        );

        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testLoadWithoutDocument(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM `document`');

        $cart = $this->generateDemoCart(2);
        $orderId = $this->persistCart($cart);

        $invoice = $this->referenceInvoiceLoader->load($orderId);

        static::assertEmpty($invoice);
    }

    public function testLoadWithoutReferenceDocumentIdWithUnsentDocuments(): void
    {
        $cart = $this->generateDemoCart(2);
        $orderId = $this->persistCart($cart);

        // Create two documents, the latest unsent invoice will be returned
        $invoiceStruct = $this->createDocument(InvoiceRenderer::TYPE, $orderId, [], $this->context)->first();
        $invoiceStructLatest = $this->createDocument(InvoiceRenderer::TYPE, $orderId, [], $this->context)->first();
        static::assertNotNull($invoiceStruct);
        static::assertNotNull($invoiceStructLatest);
        $invoice = $this->referenceInvoiceLoader->load($orderId);

        static::assertNotEmpty($invoice['id']);
        static::assertSame($invoice['id'], $invoiceStructLatest->getId());
    }

    public function testLoadWithoutReferenceDocumentIdWithOneSentDocument(): void
    {
        $cart = $this->generateDemoCart(2);
        $orderId = $this->persistCart($cart);

        // Create two documents, the latest sent invoice will be returned
        $invoiceStruct = $this->createDocument(InvoiceRenderer::TYPE, $orderId, [], $this->context)->first();
        $invoiceStructLatest = $this->createDocument(InvoiceRenderer::TYPE, $orderId, [], $this->context)->first();
        static::assertNotNull($invoiceStruct);
        static::assertNotNull($invoiceStructLatest);

        $this->connection->executeStatement(<<<'SQL'
            UPDATE document SET sent = 1 WHERE id = :id;
        SQL, ['id' => Uuid::fromHexToBytes($invoiceStruct->getId())]);

        $invoice = $this->referenceInvoiceLoader->load($orderId);

        static::assertNotEmpty($invoice['id']);
        static::assertSame($invoice['id'], $invoiceStruct->getId());
    }

    public function testLoadWithReferenceDocumentId(): void
    {
        $cart = $this->generateDemoCart(2);
        $orderId = $this->persistCart($cart);

        // Create two documents, the one with passed referenceInvoiceId will be returned
        $invoiceStruct = $this->createDocument(InvoiceRenderer::TYPE, $orderId, [], $this->context)->first();
        static::assertNotNull($invoiceStruct);
        $this->createDocument(InvoiceRenderer::TYPE, $orderId, [], $this->context)->first();

        $invoice = $this->referenceInvoiceLoader->load($orderId, $invoiceStruct->getId(), $invoiceStruct->getDeepLinkCode());

        static::assertSame($invoiceStruct->getId(), $invoice['id']);
        static::assertSame($orderId, $invoice['orderId']);
        static::assertSame(Defaults::LIVE_VERSION, $invoice['orderVersionId']);
        static::assertNotEmpty($invoice['documentNumber']);
    }
}
