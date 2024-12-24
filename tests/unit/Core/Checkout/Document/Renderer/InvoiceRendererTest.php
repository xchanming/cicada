<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Renderer;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigCollection;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigDefinition;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigEntity;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfigSalesChannel\DocumentBaseConfigSalesChannelCollection;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfigSalesChannel\DocumentBaseConfigSalesChannelEntity;
use Cicada\Core\Checkout\Document\DocumentCollection;
use Cicada\Core\Checkout\Document\DocumentEntity;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\DocumentRendererConfig;
use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Renderer\RenderedDocument;
use Cicada\Core\Checkout\Document\Service\DocumentConfigLoader;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Cicada\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\TaxFreeConfig;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\Locale\LocaleEntity;
use Cicada\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 *
 * @phpstan-type OrderSettings array{accountType: string, isCountryCompanyTaxFree: bool, setOrderDelivery: bool, setShippingCountry: bool, setEuCountry: bool}
 * @phpstan-type InvoiceConfig array{displayAdditionalNoteDelivery: bool, deliveryCountries: array<string>}
 */
#[Package('checkout')]
#[CoversClass(InvoiceRenderer::class)]
class InvoiceRendererTest extends TestCase
{
    private const COUNTRY_ID = 'country-id';

    /**
     * @param OrderSettings $orderSettings
     * @param InvoiceConfig $config
     */
    #[DataProvider('configDataProvider')]
    public function testRenderIsAllowIntraCommunityDelivery(
        array $orderSettings,
        array $config,
        bool $expectedResult
    ): void {
        $context = Context::createDefaultContext();

        $order = $this->createOrder($orderSettings);
        $orderId = $order->getId();
        $orderCollection = new OrderCollection([$order]);
        $orderSearchResult = new EntitySearchResult(OrderDefinition::ENTITY_NAME, 1, $orderCollection, null, new Criteria(), $context);

        $documentConfigSearchResult = $this->createDocumentConfigSearchResult($config, $context);

        $documentConfigRepository = $this->createMock(EntityRepository::class);
        $documentConfigRepository->method('search')->willReturn($documentConfigSearchResult);

        $documentConfigLoaderMock = new DocumentConfigLoader($documentConfigRepository);

        $ordersLanguageId = [
            [
                'language_id' => Defaults::LANGUAGE_SYSTEM,
                'ids' => $orderId,
            ],
        ];
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('fetchAllAssociative')->willReturn($ordersLanguageId);

        $orderRepositoryMock = $this->createMock(EntityRepository::class);
        $orderRepositoryMock->method('search')->willReturn($orderSearchResult);

        $documentTemplateRenderer = $this->createMock(DocumentTemplateRenderer::class);
        $documentTemplateRenderer->method('render')->willReturn('HTML');

        $invoiceRenderer = new InvoiceRenderer(
            $orderRepositoryMock,
            $documentConfigLoaderMock,
            $this->createMock(EventDispatcherInterface::class),
            $documentTemplateRenderer,
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            '',
            $connectionMock,
        );

        $operations = [
            $orderId => new DocumentGenerateOperation(
                $orderId
            ),
        ];

        $result = $invoiceRenderer->render($operations, $context, new DocumentRendererConfig());

        $successResults = $result->getSuccess();
        static::assertCount(1, $successResults);
        static::assertCount(0, $result->getErrors());
        static::assertArrayHasKey($orderId, $successResults);
        static::assertInstanceOf(RenderedDocument::class, $successResults[$orderId]);

        if ($expectedResult) {
            static::assertTrue($successResults[$orderId]->getConfig()['intraCommunityDelivery']);
        } else {
            static::assertFalse($successResults[$orderId]->getConfig()['intraCommunityDelivery']);
        }
    }

    public function testLanguageIdChainAssignedCorrectly(): void
    {
        $context = Context::createDefaultContext();

        $order = $this->createOrder([
            'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
            'isCountryCompanyTaxFree' => true,
            'setOrderDelivery' => true,
            'setShippingCountry' => true,
            'setEuCountry' => true,
        ]);

        $orderId = $order->getId();
        $orderCollection = new OrderCollection([$order]);
        $orderSearchResult = new EntitySearchResult(OrderDefinition::ENTITY_NAME, 1, $orderCollection, null, new Criteria(), $context);

        $DELanguageId = Uuid::randomHex();

        $ordersLanguageId = [
            [
                'language_id' => $DELanguageId,
                'ids' => $orderId,
            ],
            [
                'language_id' => Defaults::LANGUAGE_SYSTEM,
                'ids' => $orderId,
            ],
        ];

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('fetchAllAssociative')->willReturn($ordersLanguageId);

        $orderRepositoryMock = $this->createMock(EntityRepository::class);
        $orderRepositoryMock->method('search')->willReturnCallback(function (Criteria $criteria, Context $context) use (&$userCallCount, $DELanguageId, $orderSearchResult) {
            ++$userCallCount;

            switch ($userCallCount) {
                case 1:
                    static::assertCount(2, $context->getLanguageIdChain());
                    static::assertContains(Defaults::LANGUAGE_SYSTEM, $context->getLanguageIdChain());
                    static::assertContains($DELanguageId, $context->getLanguageIdChain());

                    break;
                case 2:
                    static::assertCount(1, $context->getLanguageIdChain());
                    static::assertContains(Defaults::LANGUAGE_SYSTEM, $context->getLanguageIdChain());
            }

            return $orderSearchResult;
        });

        $documentTemplateRenderer = $this->createMock(DocumentTemplateRenderer::class);
        $documentTemplateRenderer->method('render')->willReturn('HTML');

        $invoiceRenderer = new InvoiceRenderer(
            $orderRepositoryMock,
            new DocumentConfigLoader($this->createMock(EntityRepository::class)),
            $this->createMock(EventDispatcherInterface::class),
            $documentTemplateRenderer,
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            '',
            $connectionMock,
        );

        $operations = [
            $orderId => new DocumentGenerateOperation(
                $orderId
            ),
        ];

        $invoiceRenderer->render($operations, $context, new DocumentRendererConfig());
    }

    public function testDoNotForceDocumentCreation(): void
    {
        Feature::skipTestIfInActive('v6.7.0.0', $this);

        $context = Context::createDefaultContext();

        $document = new DocumentEntity();
        $document->setId(Uuid::randomHex());

        $order = $this->createOrder([
            'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
            'isCountryCompanyTaxFree' => true,
            'setOrderDelivery' => true,
            'setShippingCountry' => true,
            'setEuCountry' => true,
        ]);

        $order->setDocuments(new DocumentCollection([$document]));

        $orderId = $order->getId();
        $orderCollection = new OrderCollection([$order]);
        $orderSearchResult = new EntitySearchResult(OrderDefinition::ENTITY_NAME, 1, $orderCollection, null, new Criteria(), $context);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('fetchAllAssociative')->willReturn([
            [
                'language_id' => Defaults::LANGUAGE_SYSTEM,
                'ids' => $orderId,
            ],
        ]);

        $orderRepositoryMock = $this->createMock(EntityRepository::class);
        $orderRepositoryMock->method('search')->willReturn($orderSearchResult);

        $documentTemplateRenderer = $this->createMock(DocumentTemplateRenderer::class);
        $documentTemplateRenderer->expects(static::never())->method('render');

        $documentConfigLoaderMock = new DocumentConfigLoader($this->createMock(EntityRepository::class));

        $invoiceRenderer = new InvoiceRenderer(
            $orderRepositoryMock,
            $documentConfigLoaderMock,
            $this->createMock(EventDispatcherInterface::class),
            $documentTemplateRenderer,
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            '',
            $connectionMock,
        );

        $operations = [
            $orderId => new DocumentGenerateOperation(
                $orderId,
                FileTypes::PDF,
                ['forceDocumentCreation' => false],
            ),
        ];

        $result = $invoiceRenderer->render($operations, $context, new DocumentRendererConfig());

        $successResults = $result->getSuccess();

        static::assertCount(0, $successResults);
    }

    public static function configDataProvider(): \Generator
    {
        yield 'will return true because all necessary configs are made' => [
            'orderSettings' => [
                'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
                'isCountryCompanyTaxFree' => true,
                'setOrderDelivery' => true,
                'setShippingCountry' => true,
                'setEuCountry' => true,
            ],
            'config' => [
                'displayAdditionalNoteDelivery' => true,
            ],
            'expectedResult' => true,
        ];

        yield 'will return false because customer is no B2B customer' => [
            'orderSettings' => [
                'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
                'isCountryCompanyTaxFree' => true,
                'setOrderDelivery' => true,
                'setShippingCountry' => true,
                'setEuCountry' => true,
            ],
            'config' => [
                'displayAdditionalNoteDelivery' => true,
            ],
            'expectedResult' => false,
        ];

        yield 'will return false because country setting "CompanyTaxFree" is not activated' => [
            'orderSettings' => [
                'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
                'isCountryCompanyTaxFree' => false,
                'setOrderDelivery' => true,
                'setShippingCountry' => true,
                'setEuCountry' => true,
            ],
            'config' => [
                'displayAdditionalNoteDelivery' => true,
            ],
            'expectedResult' => false,
        ];

        yield 'will return false because customer address is not part of "Member countries"' => [
            'orderSettings' => [
                'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
                'isCountryCompanyTaxFree' => true,
                'setOrderDelivery' => true,
                'setShippingCountry' => true,
                'setEuCountry' => false,
            ],
            'config' => [
                'displayAdditionalNoteDelivery' => true,
            ],
            'expectedResult' => false,
        ];

        yield 'will return false because "intra-Community delivery" label is not activated' => [
            'orderSettings' => [
                'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
                'isCountryCompanyTaxFree' => true,
                'setOrderDelivery' => true,
                'setShippingCountry' => true,
                'setEuCountry' => true,
            ],
            'config' => [
                'displayAdditionalNoteDelivery' => false,
            ],
            'expectedResult' => false,
        ];

        yield 'will return false because no order-deliveries exist' => [
            'orderSettings' => [
                'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
                'isCountryCompanyTaxFree' => true,
                'setOrderDelivery' => false,
                'setShippingCountry' => false,
                'setEuCountry' => true,
            ],
            'config' => [
                'displayAdditionalNoteDelivery' => true,
            ],
            'expectedResult' => false,
        ];

        yield 'will return false because no shipping-country is set' => [
            'orderSettings' => [
                'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
                'isCountryCompanyTaxFree' => true,
                'setOrderDelivery' => true,
                'setShippingCountry' => false,
                'setEuCountry' => true,
            ],
            'config' => [
                'displayAdditionalNoteDelivery' => true,
            ],
            'expectedResult' => false,
        ];
    }

    /**
     * @param OrderSettings $orderSettings
     */
    private function createOrder(array $orderSettings): OrderEntity
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setId($salesChannelId);

        $language = new LanguageEntity();
        $language->setId('language-test-id');
        $localeEntity = new LocaleEntity();
        $localeEntity->setCode('en-GB');
        $language->setLocale($localeEntity);

        $orderId = Uuid::randomHex();
        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setVersionId(Defaults::LIVE_VERSION);
        $order->setSalesChannelId($salesChannelId);
        $order->setLanguage($language);
        $order->setLanguageId('language-test-id');

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setAccountType($orderSettings['accountType']);
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setOrder($order);
        $orderCustomer->setCustomer($customer);
        $order->setOrderCustomer($orderCustomer);

        if ($orderSettings['setOrderDelivery']) {
            $delivery = new OrderDeliveryEntity();
            $delivery->setId(Uuid::randomHex());
            $deliveries = new OrderDeliveryCollection([$delivery]);
            $order->setDeliveries($deliveries);
        }

        if ($orderSettings['setShippingCountry'] && $orderSettings['setOrderDelivery']) {
            $country = new CountryEntity();
            $country->setId(self::COUNTRY_ID);
            if ($orderSettings['setEuCountry']) {
                $country->setIsEu(true);
            } else {
                $country->setIsEu(false);
            }
            $country->setCompanyTax(new TaxFreeConfig($orderSettings['isCountryCompanyTaxFree'], Defaults::CURRENCY, 0));
            $address = new OrderAddressEntity();
            $address->setCountry($country);
            $delivery->setShippingOrderAddress($address);
        }

        return $order;
    }

    /**
     * @param InvoiceConfig $config
     *
     * @return EntitySearchResult<DocumentBaseConfigCollection>
     */
    private function createDocumentConfigSearchResult(array $config, Context $context): EntitySearchResult
    {
        $documentBaseConfigEntity = new DocumentBaseConfigEntity();
        $documentBaseConfigEntity->setId(Uuid::randomHex());

        $documentBaseConfigSalesChannel = new DocumentBaseConfigSalesChannelEntity();
        $documentBaseConfigSalesChannel->setId(Uuid::randomHex());

        $documentBaseConfigEntity->setSalesChannels(new DocumentBaseConfigSalesChannelCollection([$documentBaseConfigSalesChannel]));
        $documentBaseConfigEntity->setConfig($config);
        $documentBaseConfigCollection = new DocumentBaseConfigCollection([$documentBaseConfigEntity]);

        return new EntitySearchResult(
            DocumentBaseConfigDefinition::ENTITY_NAME,
            1,
            $documentBaseConfigCollection,
            null,
            new Criteria(),
            $context
        );
    }
}
