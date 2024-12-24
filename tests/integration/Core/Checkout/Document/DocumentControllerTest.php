<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Document;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Cicada\Core\Checkout\Document\DocumentIdCollection;
use Cicada\Core\Checkout\Document\DocumentIdStruct;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Service\PdfRenderer;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\CountryAddToSalesChannelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\TestUser;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class DocumentControllerTest extends TestCase
{
    use AdminApiTestBehaviour;
    use CountryAddToSalesChannelTestBehaviour;
    use DocumentTrait;

    private SalesChannelContext $salesChannelContext;

    private Context $context;

    private Connection $connection;

    private DocumentGenerator $documentGenerator;

    private EntityRepository $orderRepository;

    private string $customerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = static::getContainer()->get(Connection::class);

        $this->context = Context::createDefaultContext();

        $paymentMethod = $this->getAvailablePaymentMethod();

        $this->customerId = $this->createCustomer();
        $shippingMethod = $this->getAvailableShippingMethod();

        $this->addCountriesToSalesChannel();

        $this->salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL,
            [
                SalesChannelContextService::CUSTOMER_ID => $this->customerId,
                SalesChannelContextService::SHIPPING_METHOD_ID => $shippingMethod->getId(),
                SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethod->getId(),
            ]
        );

        $ruleIds = [];
        if ($shippingRuleId = $shippingMethod->getAvailabilityRuleId()) {
            $ruleIds[] = $shippingRuleId;
        }
        if ($paymentRuleId = $paymentMethod->getAvailabilityRuleId()) {
            $ruleIds[] = $paymentRuleId;
        }
        $this->salesChannelContext->setRuleIds($ruleIds);

        $this->connection = static::getContainer()->get(Connection::class);

        $this->documentGenerator = static::getContainer()->get(DocumentGenerator::class);

        $this->orderRepository = static::getContainer()->get('order.repository');
    }

    public function testCustomUploadDocument(): void
    {
        $context = Context::createDefaultContext();

        /** @var EntityRepository $documentTypeRepository */
        $documentTypeRepository = static::getContainer()->get('document_type.repository');
        $criteria = (new Criteria())->addFilter(new EqualsFilter('technicalName', 'invoice'));
        /** @var DocumentTypeEntity $type */
        $type = $documentTypeRepository->search($criteria, $context)->first();
        $cart = $this->generateDemoCart(2);
        $orderId = $this->persistCart($cart);

        $documentId = Uuid::randomHex();

        $document = [
            'id' => $documentId,
            'orderId' => $orderId,
            'documentTypeId' => $type->getId(),
            'fileType' => 'pdf',
            'static' => true,
            'config' => [],
        ];

        $baseResource = '/api/';

        $this->getBrowser()->request(
            'POST',
            $baseResource . '_action/order/document/invoice/create',
            [],
            [],
            [],
            (string) json_encode([$document])
        );

        $response = json_decode((string) $this->getBrowser()->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('data', $response);

        $filename = 'invoice';
        $expectedFileContent = 'simple invoice';
        $expectedContentType = 'text/plain; charset=UTF-8';

        $this->getBrowser()->request(
            'POST',
            $baseResource . '_action/document/' . $response['data'][0]['documentId'] . '/upload?fileName=' . $filename . '&extension=txt',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => $expectedContentType, 'HTTP_CONTENT_LENGTH' => mb_strlen($expectedFileContent)],
            $expectedFileContent
        );

        $response = json_decode((string) $this->getBrowser()->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $this->getBrowser()->request('GET', $baseResource . '_action/document/' . $response['documentId'] . '/' . $response['documentDeepLink']);
        $response = $this->getBrowser()->getResponse();
        static::assertEquals(200, $response->getStatusCode());

        static::assertEquals($expectedFileContent, $response->getContent());
        static::assertEquals($expectedContentType, $response->headers->get('content-type'));
    }

    public function testPreview(): void
    {
        $cart = $this->generateDemoCart(2);
        $orderId = $this->persistCart($cart);

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search(new Criteria([$orderId]), $this->context)->get($orderId);
        static::assertNotNull($order);

        $endpoint = \sprintf('/api/_action/order/%s/%s/document/invoice/preview', Uuid::randomHex(), $order->getDeepLinkCode());
        $this->getBrowser()->request('GET', $endpoint);

        static::assertEquals($this->getBrowser()->getResponse()->getStatusCode(), Response::HTTP_NOT_FOUND);
        $response = json_decode((string) $this->getBrowser()->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertNotEmpty($response['errors']);
        static::assertEquals('DOCUMENT__GENERATION_ERROR', $response['errors'][0]['code']);

        $endpoint = \sprintf('/api/_action/order/%s/%s/document/invoice/preview', $orderId, 'wrong deep link code');
        $this->getBrowser()->request('GET', $endpoint);

        static::assertEquals($this->getBrowser()->getResponse()->getStatusCode(), Response::HTTP_NOT_FOUND);
        $response = json_decode((string) $this->getBrowser()->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertNotEmpty($response['errors']);
        static::assertEquals('DOCUMENT__GENERATION_ERROR', $response['errors'][0]['code']);

        $endpoint = \sprintf('/api/_action/order/%s/%s/document/invoice/preview', $orderId, $order->getDeepLinkCode());

        $this->getBrowser()->request('GET', $endpoint);

        static::assertEquals($this->getBrowser()->getResponse()->getStatusCode(), Response::HTTP_OK);
        static::assertNotNull($this->getBrowser()->getResponse()->getContent());
        static::assertEquals(PdfRenderer::FILE_CONTENT_TYPE, $this->getBrowser()->getResponse()->headers->get('content-type'));
    }

    public function testPreviewPermission(): void
    {
        $cart = $this->generateDemoCart(2);
        $orderId = $this->persistCart($cart);

        /** @var OrderEntity $order */
        $order = $this->orderRepository->search(new Criteria([$orderId]), $this->context)->get($orderId);
        static::assertNotNull($order);

        TestUser::createNewTestUser(
            static::getContainer()->get(Connection::class),
            []
        )->authorizeBrowser($this->getBrowser());

        $endpoint = \sprintf('/api/_action/order/%s/%s/document/invoice/preview', $orderId, $order->getDeepLinkCode());

        $this->getBrowser()->request('GET', $endpoint);

        static::assertEquals(Response::HTTP_FORBIDDEN, $this->getBrowser()->getResponse()->getStatusCode());
        $response = json_decode((string) $this->getBrowser()->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertNotEmpty($response['errors']);
        static::assertEquals($response['errors'][0]['code'], 'FRAMEWORK__MISSING_PRIVILEGE_ERROR');

        TestUser::createNewTestUser(
            static::getContainer()->get(Connection::class),
            ['document:read']
        )->authorizeBrowser($this->getBrowser());

        $this->getBrowser()->request('GET', $endpoint);

        static::assertEquals($this->getBrowser()->getResponse()->getStatusCode(), Response::HTTP_OK);
        static::assertNotNull($this->getBrowser()->getResponse()->getContent());
        static::assertEquals(PdfRenderer::FILE_CONTENT_TYPE, $this->getBrowser()->getResponse()->headers->get('content-type'));
    }

    public function testCreateDocuments(): void
    {
        static::assertNotNull($customer = $this->salesChannelContext->getCustomer());
        $order1 = $this->createOrder($customer->getId(), $this->context);
        $order2 = $this->createOrder($customer->getId(), $this->context);
        $this->createDocument(InvoiceRenderer::TYPE, $order1->getId(), [
            'documentType' => 'invoice',
            'custom' => [
                'invoiceNumber' => '1100',
            ],
        ], $this->context);

        $this->createDocument(InvoiceRenderer::TYPE, $order2->getId(), [
            'documentType' => 'invoice',
            'documentRangerType' => 'document_invoice',
            'custom' => [
                'invoiceNumber' => '1101',
            ],
        ], $this->context);

        $requests = [
            'invoice' => [
                [
                    'orderId' => $order1->getId(),
                ],
                [
                    'orderId' => $order2->getId(),
                ],
            ],
            'credit_note' => [
                [
                    'orderId' => $order1->getId(),
                ],
                [
                    'orderId' => $order2->getId(),
                ],
            ],
            'delivery_note' => [
                [
                    'orderId' => $order1->getId(),
                ],
                [
                    'orderId' => $order2->getId(),
                ],
            ],
            'storno' => [
                [
                    'orderId' => $order1->getId(),
                ],
                [
                    'orderId' => $order2->getId(),
                ],
            ],
        ];

        $documentIds = [];

        foreach ($requests as $type => $payload) {
            $this->getBrowser()->request(
                'POST',
                \sprintf('/api/_action/order/document/%s/create', $type),
                [],
                [],
                [],
                (string) json_encode($payload)
            );

            $response = $this->getBrowser()->getResponse();
            static::assertEquals(200, $response->getStatusCode());
            $response = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
            static::assertArrayHasKey('data', $response);
            static::assertNotEmpty($data = $response['data']);
            static::assertCount(2, $data);

            $documentIds = array_merge($documentIds, $this->getDocumentIds($data));
        }

        $documents = $this->getDocumentByDocumentIds($documentIds);

        static::assertNotEmpty($documents);
        static::assertCount(8, $documents);
    }

    public function testCreateDocumentWithInvalidDocumentTypeName(): void
    {
        static::assertNotNull($customer = $this->salesChannelContext->getCustomer());
        $order = $this->createOrder($customer->getId(), $this->context);
        $content = [
            [
                'orderId' => $order->getId(),
                'fileType' => 'MP3',
            ],
        ];

        $this->getBrowser()->request(
            'POST',
            '/api/_action/order/document/receipt/create',
            [],
            [],
            [],
            (string) json_encode($content)
        );

        $response = json_decode((string) $this->getBrowser()->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertEquals(400, $this->getBrowser()->getResponse()->getStatusCode());
        static::assertNotEmpty($response['errors']);
        static::assertEquals('DOCUMENT__INVALID_RENDERER_TYPE', $response['errors'][0]['code']);
    }

    public function testCreateStornoDocumentsWithoutInvoiceDocument(): void
    {
        static::assertNotNull($customer = $this->salesChannelContext->getCustomer());
        $order = $this->createOrder($customer->getId(), $this->context);

        $content = [
            [
                'orderId' => $order->getId(),
                'fileType' => FileTypes::PDF,
            ],
        ];

        $this->getBrowser()->request(
            'POST',
            '/api/_action/order/document/storno/create',
            [],
            [],
            [],
            (string) json_encode($content)
        );

        $response = $this->getBrowser()->getResponse();

        $response = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertEquals(200, $this->getBrowser()->getResponse()->getStatusCode());
        static::assertArrayHasKey('errors', $response);
        static::assertEquals('DOCUMENT__GENERATION_ERROR', $response['errors'][$order->getId()][0]['code']);
    }

    public function testDownloadWithoutDocuments(): void
    {
        $this->getBrowser()->request(
            'POST',
            '/api/_action/order/document/download',
            [],
            [],
            [],
            (string) json_encode([])
        );

        static::assertIsString($this->getBrowser()->getResponse()->getContent());
        $response = json_decode((string) $this->getBrowser()->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertEquals(400, $this->getBrowser()->getResponse()->getStatusCode());
        static::assertArrayHasKey('errors', $response);
        static::assertEquals('FRAMEWORK__INVALID_REQUEST_PARAMETER', $response['errors'][0]['code']);

        $this->getBrowser()->request(
            'POST',
            '/api/_action/order/document/download',
            [],
            [],
            [],
            (string) json_encode([
                'documentIds' => [Uuid::randomHex()],
            ])
        );

        static::assertEquals(204, $this->getBrowser()->getResponse()->getStatusCode());
    }

    public function testDownload(): void
    {
        $order = $this->createOrder($this->customerId, $this->context);
        $documentTypes = [
            'invoice' => [
                'documentType' => 'invoice',
                'documentRangerType' => 'document_invoice',
                'documentNumber' => '1100',
                'custom' => [
                    'invoiceNumber' => '1100',
                ],
            ],
        ];

        static::assertNotNull($document = $this->createDocuments($order->getId(), $documentTypes, $this->context)->first());
        static::assertInstanceOf(DocumentIdStruct::class, $document);

        $documentId = $document->getId();

        $this->getBrowser()->request(
            'POST',
            '/api/_action/order/document/download',
            [],
            [],
            [],
            (string) json_encode([
                'documentIds' => [$documentId],
            ])
        );

        $response = $this->getBrowser()->getResponse();

        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function testDownloadPermission(): void
    {
        TestUser::createNewTestUser(
            static::getContainer()->get(Connection::class),
            []
        )->authorizeBrowser($this->getBrowser());

        $this->getBrowser()->request('POST', '/api/_action/order/document/download');

        static::assertEquals($this->getBrowser()->getResponse()->getStatusCode(), Response::HTTP_FORBIDDEN);
        $response = json_decode((string) $this->getBrowser()->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertNotEmpty($response['errors']);
        static::assertEquals($response['errors'][0]['code'], 'FRAMEWORK__MISSING_PRIVILEGE_ERROR');

        TestUser::createNewTestUser(
            static::getContainer()->get(Connection::class),
            ['document:read']
        )->authorizeBrowser($this->getBrowser());

        $order = $this->createOrder($this->customerId, $this->context);
        $documentTypes = [
            'invoice' => [
                'documentType' => 'invoice',
                'documentRangerType' => 'document_invoice',
                'documentNumber' => '1100',
                'custom' => [
                    'invoiceNumber' => '1100',
                ],
            ],
        ];

        static::assertNotNull($document = $this->createDocuments($order->getId(), $documentTypes, $this->context)->first());
        static::assertInstanceOf(DocumentIdStruct::class, $document);
        $documentId = $document->getId();

        $this->getBrowser()->request(
            'POST',
            '/api/_action/order/document/download',
            [],
            [],
            [],
            (string) json_encode([
                'documentIds' => [$documentId],
            ])
        );

        $response = $this->getBrowser()->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    private function createOrder(string $customerId, Context $context): OrderEntity
    {
        $orderId = Uuid::randomHex();
        $stateId = static::getContainer()->get(InitialStateIdLoader::class)->get(OrderStates::STATE_MACHINE);
        $billingAddressId = Uuid::randomHex();

        $order = [
            'id' => $orderId,
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'orderNumber' => Uuid::randomHex(),
            'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'orderCustomer' => [
                'customerId' => $customerId,
                'email' => 'test@example.com',
                'salutationId' => $this->getValidSalutationId(),
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
            ],
            'stateId' => $stateId,
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1.0,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'billingAddressId' => $billingAddressId,
            'addresses' => [
                [
                    'id' => $billingAddressId,
                    'salutationId' => $this->getValidSalutationId(),
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                    'countryId' => $this->getValidCountryId(),
                ],
            ],
            'lineItems' => [
                [
                    'id' => Uuid::randomHex(),
                    'identifier' => Uuid::randomHex(),
                    'quantity' => 1,
                    'label' => 'label',
                    'type' => LineItem::CREDIT_LINE_ITEM_TYPE,
                    'price' => new CalculatedPrice(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'priceDefinition' => new QuantityPriceDefinition(200, new TaxRuleCollection(), 2),
                ],
            ],
            'deliveries' => [
            ],
            'context' => '{}',
            'payload' => '{}',
        ];

        $this->orderRepository->upsert([$order], $context);
        $order = $this->orderRepository->search(new Criteria([$orderId]), $context)->first();
        static::assertInstanceOf(OrderEntity::class, $order);

        return $order;
    }

    /**
     * @param array<string, string[]> $data
     *
     * @return mixed[]
     */
    private function getDocumentIds(array $data): array
    {
        $ids = [];

        foreach ($data as $value) {
            $ids[] = $value['documentId'];
        }

        return $ids;
    }

    /**
     * @param array<string|int, string> $documentIds
     *
     * @return array<string|int, string|array<string, mixed>>
     */
    private function getDocumentByDocumentIds(array $documentIds): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT `id`
                    FROM `document`
                    WHERE hex(`id`) IN (:documentIds)',
            [
                'documentIds' => $documentIds,
            ],
            ['documentIds' => ArrayParameterType::BINARY]
        );
    }

    /**
     * @param array<string, array<string, array<string, string>|string>> $documentTypes
     */
    private function createDocuments(string $orderId, array $documentTypes, Context $context): DocumentIdCollection
    {
        $operations = [];

        $collection = new DocumentIdCollection();

        foreach ($documentTypes as $documentType => $config) {
            $operation = new DocumentGenerateOperation($orderId, FileTypes::PDF, $config);
            $operations[$orderId] = $operation;

            $result = $this->documentGenerator->generate($documentType, $operations, $context);

            static::assertNotNull($document = $result->getSuccess()->first());
            $collection->add($document);
        }

        return $collection;
    }
}
