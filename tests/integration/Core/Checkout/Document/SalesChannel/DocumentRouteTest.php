<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Document\SalesChannel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Document\DocumentIdStruct;
use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Service\DocumentConfigLoader;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Content\Test\Flow\OrderActionTrait;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class DocumentRouteTest extends TestCase
{
    use CustomerTestTrait, OrderActionTrait {
        OrderActionTrait::login insteadof CustomerTestTrait;
    }
    use IntegrationTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    private DocumentGenerator $documentGenerator;

    private string $customerId;

    private string $guestId;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);
        $this->documentGenerator = static::getContainer()->get(DocumentGenerator::class);
        static::getContainer()->get(DocumentConfigLoader::class)->reset();
        $this->customerId = $this->createCustomer();
        $this->guestId = $this->createCustomer('guest@example.com', true);
        $this->createOrder($this->customerId);
    }

    #[DataProvider('documentDownloadRouteDataProvider')]
    public function testDownload(bool $isGuest, ?bool $withValidDeepLinkCode, \Closure $assertionCallback): void
    {
        $token = $this->getLoggedInContextToken($isGuest ? $this->guestId : $this->customerId, $this->ids->get('sales-channel'));

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $token);

        $operation = new DocumentGenerateOperation($this->ids->get('order'));
        $document = $this->documentGenerator->generate(InvoiceRenderer::TYPE, [$operation->getOrderId() => $operation], Context::createDefaultContext())->getSuccess()->first();
        static::assertInstanceOf(DocumentIdStruct::class, $document);
        $deepLinkCode = '';

        if ($withValidDeepLinkCode !== null) {
            $deepLinkCode = $withValidDeepLinkCode ? $document->getDeepLinkCode() : Uuid::randomHex();
        }

        $endpoint = \sprintf('/store-api/document/download/%s', $document->getId());

        if ($deepLinkCode !== '') {
            $endpoint .= '/' . $deepLinkCode;
        }

        $this->browser
            ->request(
                'GET',
                $endpoint,
                [
                ]
            );

        $response = $this->browser->getResponse();

        $assertionCallback($response);
    }

    public static function documentDownloadRouteDataProvider(): \Generator
    {
        yield 'guest with valid deep link code' => [
            true,
            true,
            function (Response $response): void {
                $headers = $response->headers;

                static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
                static::assertNotEmpty($response->getContent());
                static::assertEquals('inline; filename=invoice_1000.pdf', $headers->get('content-disposition'));
                static::assertEquals('application/pdf', $headers->get('content-type'));
            },
        ];
        yield 'guest with invalid deep link code' => [
            true,
            false,
            function (Response $response): void {
                static::assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
                $response = json_decode($response->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
                static::assertArrayHasKey('errors', $response);
                static::assertSame('DOCUMENT__DOCUMENT_NOT_FOUND', $response['errors'][0]['code']);
            },
        ];
        yield 'guest without deep link code' => [
            true,
            null,
            function (Response $response): void {
                static::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
                $response = json_decode($response->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
                static::assertArrayHasKey('errors', $response);
                static::assertSame('CHECKOUT__CUSTOMER_NOT_LOGGED_IN', $response['errors'][0]['code']);
            },
        ];
        yield 'customer with deep valid link code' => [
            false,
            true,
            function (Response $response): void {
                $headers = $response->headers;

                static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
                static::assertNotEmpty($response->getContent());
                static::assertEquals('inline; filename=invoice_1000.pdf', $headers->get('content-disposition'));
                static::assertEquals('application/pdf', $headers->get('content-type'));
            },
        ];
        yield 'customer with invalid deep link code' => [
            false,
            false,
            function (Response $response): void {
                static::assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
                $response = json_decode($response->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
                static::assertArrayHasKey('errors', $response);
                static::assertSame('DOCUMENT__DOCUMENT_NOT_FOUND', $response['errors'][0]['code']);
            },
        ];
        yield 'customer without deep link code' => [
            false,
            null,
            function (Response $response): void {
                $headers = $response->headers;

                static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
                static::assertNotEmpty($response->getContent());
                static::assertEquals('inline; filename=invoice_1000.pdf', $headers->get('content-disposition'));
                static::assertEquals('application/pdf', $headers->get('content-type'));
            },
        ];
    }
}
