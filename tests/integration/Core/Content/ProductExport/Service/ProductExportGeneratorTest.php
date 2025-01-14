<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ProductExport\Service;

use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\ProductExport\Event\ProductExportChangeEncodingEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportLoggingEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportProductCriteriaEvent;
use Cicada\Core\Content\ProductExport\Event\ProductExportRenderBodyContextEvent;
use Cicada\Core\Content\ProductExport\ProductExportEntity;
use Cicada\Core\Content\ProductExport\ProductExportException;
use Cicada\Core\Content\ProductExport\Service\ProductExportGenerator;
use Cicada\Core\Content\ProductExport\Service\ProductExportGeneratorInterface;
use Cicada\Core\Content\ProductExport\Service\ProductExportRenderer;
use Cicada\Core\Content\ProductExport\Service\ProductExportValidator;
use Cicada\Core\Content\ProductExport\Struct\ExportBehavior;
use Cicada\Core\Content\ProductExport\Struct\ProductExportResult;
use Cicada\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Cicada\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\Adapter\Twig\TwigVariableParserFactory;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProductExportGeneratorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $repository;

    private Context $context;

    private ProductExportGeneratorInterface $service;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('product_export.repository');
        $this->service = static::getContainer()->get(ProductExportGenerator::class);
        $this->context = Context::createDefaultContext();
    }

    public function testExport(): void
    {
        $productExportId = $this->createTestEntity();

        $criteria = $this->createProductExportCriteria($productExportId);

        $productExport = $this->repository->search($criteria, $this->context)->first();
        static::assertInstanceOf(ProductExportEntity::class, $productExport);

        $exportResult = $this->service->generate($productExport, new ExportBehavior());

        static::assertInstanceOf(ProductExportResult::class, $exportResult);
        static::assertStringEqualsFile(__DIR__ . '/fixtures/test-export.csv', $exportResult->getContent());
    }

    public function testProductExportGenerationEvents(): void
    {
        $productExportId = $this->createTestEntity();

        $criteria = $this->createProductExportCriteria($productExportId);

        $productExport = $this->repository->search($criteria, $this->context)->first();

        static::assertInstanceOf(ProductExportEntity::class, $productExport);

        $exportBehavior = new ExportBehavior();

        $eventDispatcher = static::getContainer()->get('event_dispatcher');

        $productExportProductCriteriaEventDispatched = false;
        $productExportProductCriteriaCallback = function () use (
            &$productExportProductCriteriaEventDispatched
        ): void {
            $productExportProductCriteriaEventDispatched = true;
        };
        $eventDispatcher->addListener(
            ProductExportProductCriteriaEvent::class,
            $productExportProductCriteriaCallback
        );

        $productExportRenderBodyContextEventDispatched = false;
        $productExportRenderBodyContextCallback = function () use (
            &$productExportRenderBodyContextEventDispatched
        ): void {
            $productExportRenderBodyContextEventDispatched = true;
        };
        $eventDispatcher->addListener(
            ProductExportRenderBodyContextEvent::class,
            $productExportRenderBodyContextCallback
        );

        $productExportChangeEncodingEventDispatched = false;
        $productExportChangeEncodingCallback = function () use (
            &$productExportChangeEncodingEventDispatched
        ): void {
            $productExportChangeEncodingEventDispatched = true;
        };
        $eventDispatcher->addListener(
            ProductExportChangeEncodingEvent::class,
            $productExportChangeEncodingCallback
        );

        $exportGenerator = new ProductExportGenerator(
            static::getContainer()->get(ProductStreamBuilder::class),
            static::getContainer()->get('sales_channel.product.repository'),
            static::getContainer()->get(ProductExportRenderer::class),
            $eventDispatcher,
            static::getContainer()->get(ProductExportValidator::class),
            static::getContainer()->get(SalesChannelContextService::class),
            static::getContainer()->get(Translator::class),
            static::getContainer()->get(SalesChannelContextPersister::class),
            static::getContainer()->get(Connection::class),
            100,
            static::getContainer()->get(SeoUrlPlaceholderHandlerInterface::class),
            static::getContainer()->get('twig'),
            static::getContainer()->get(ProductDefinition::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            static::getContainer()->get(TwigVariableParserFactory::class)
        );

        $exportGenerator->generate($productExport, $exportBehavior);

        static::assertTrue($productExportProductCriteriaEventDispatched, 'ProductExportProductCriteriaEvent was not dispatched');
        static::assertTrue($productExportRenderBodyContextEventDispatched, 'ProductExportRenderBodyContextEvent was not dispatched');
        static::assertTrue($productExportChangeEncodingEventDispatched, 'ProductExportChangeEncodingEvent was not dispatched');

        $eventDispatcher->removeListener(ProductExportProductCriteriaEvent::class, $productExportProductCriteriaCallback);
        $eventDispatcher->removeListener(ProductExportRenderBodyContextEvent::class, $productExportRenderBodyContextCallback);
        $eventDispatcher->removeListener(ProductExportChangeEncodingEvent::class, $productExportChangeEncodingCallback);
    }

    public function testEmptyProductExportGenerationEvents(): void
    {
        $productExportId = $this->createTestEntity();

        $criteria = $this->createProductExportCriteria($productExportId);

        $productExport = $this->repository->search($criteria, $this->context)->first();
        static::assertInstanceOf(ProductExportEntity::class, $productExport);

        $exportBehavior = new ExportBehavior();

        $eventDispatcher = static::getContainer()->get('event_dispatcher');

        $productExportProductCriteriaEventDispatched = false;
        $productExportProductCriteriaCallback = function (ProductExportProductCriteriaEvent $event) use (
            &$productExportProductCriteriaEventDispatched
        ): void {
            $productExportProductCriteriaEventDispatched = true;
            // Change filters to guarantee empty export for this test
            $event->getCriteria()->addFilter(new EqualsFilter('active', true));
            $event->getCriteria()->addFilter(new EqualsFilter('active', false));
        };
        $eventDispatcher->addListener(
            ProductExportProductCriteriaEvent::class,
            $productExportProductCriteriaCallback
        );

        $productExportLoggingEventDispatched = false;
        $productExportLoggingCallback = function () use (
            &$productExportLoggingEventDispatched
        ): void {
            $productExportLoggingEventDispatched = true;
        };
        $eventDispatcher->addListener(
            ProductExportLoggingEvent::class,
            $productExportLoggingCallback
        );

        $exportGenerator = new ProductExportGenerator(
            static::getContainer()->get(ProductStreamBuilder::class),
            static::getContainer()->get('sales_channel.product.repository'),
            static::getContainer()->get(ProductExportRenderer::class),
            $eventDispatcher,
            static::getContainer()->get(ProductExportValidator::class),
            static::getContainer()->get(SalesChannelContextService::class),
            static::getContainer()->get(Translator::class),
            static::getContainer()->get(SalesChannelContextPersister::class),
            static::getContainer()->get(Connection::class),
            100,
            static::getContainer()->get(SeoUrlPlaceholderHandlerInterface::class),
            static::getContainer()->get('twig'),
            static::getContainer()->get(ProductDefinition::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            static::getContainer()->get(TwigVariableParserFactory::class)
        );

        try {
            $exportGenerator->generate($productExport, $exportBehavior);
        } catch (ProductExportException) {
        }

        static::assertTrue($productExportProductCriteriaEventDispatched, 'ProductExportProductCriteriaEvent was not dispatched');
        static::assertTrue($productExportLoggingEventDispatched, 'ProductExportLoggingEvent was not dispatched');

        $eventDispatcher->removeListener(ProductExportLoggingEvent::class, $productExportLoggingCallback);
        $eventDispatcher->removeListener(ProductExportProductCriteriaEvent::class, $productExportProductCriteriaCallback);
    }

    public function testExportWithNestedAssociations(): void
    {
        $productExportId = $this->createTestEntity([
            'bodyTemplate' => '{{ product.name }},{{ product.stock }},{{ product.options.first.group.name }}',
        ]);

        $criteria = $this->createProductExportCriteria($productExportId);

        $productExport = $this->repository->search($criteria, $this->context)->first();
        static::assertInstanceOf(ProductExportEntity::class, $productExport);

        $exportResult = $this->service->generate($productExport, new ExportBehavior());

        static::assertInstanceOf(ProductExportResult::class, $exportResult);
        static::assertStringContainsString('options-group', $exportResult->getContent());
    }

    public function testExportWithForLoop(): void
    {
        $productExportId = $this->createTestEntity([
            'bodyTemplate' => '{% for foo in product.options %} {{ foo.group.name }} {% endfor %}',
        ]);

        $criteria = $this->createProductExportCriteria($productExportId);

        $productExport = $this->repository->search($criteria, $this->context)->first();
        static::assertInstanceOf(ProductExportEntity::class, $productExport);

        $exportResult = $this->service->generate($productExport, new ExportBehavior());

        static::assertInstanceOf(ProductExportResult::class, $exportResult);
        static::assertStringContainsString('options-group', $exportResult->getContent());
    }

    #[DataProvider('isoCodeProvider')]
    public function testExportRendersGivenCurrencies(string $code): void
    {
        $productExportId = $this->createTestEntity([
            'currencyId' => $this->getCurrencyIdByIso($code),
            'bodyTemplate' => '{{ context.currency.isoCode }}',
        ]);

        $criteria = $this->createProductExportCriteria($productExportId);

        $productExport = $this->repository->search($criteria, $this->context)->first();
        static::assertInstanceOf(ProductExportEntity::class, $productExport);

        $result = $this->service->generate($productExport, new ExportBehavior());

        static::assertInstanceOf(ProductExportResult::class, $result);
        static::assertStringContainsString($code, $result->getContent());
    }

    public static function isoCodeProvider(): \Generator
    {
        yield 'CNY iso code' => ['CNY'];
        yield 'US dollar iso code' => ['USD'];
    }

    private function createProductExportCriteria(string $id): Criteria
    {
        $criteria = new Criteria([$id]);
        $criteria->addAssociations([
            'salesChannel',
            'salesChannelDomain.language',
        ]);

        return $criteria;
    }

    private function getSalesChannelId(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('sales_channel.repository');

        $salesChannel = $repository->search(new Criteria(), $this->context)->first();
        static::assertInstanceOf(SalesChannelEntity::class, $salesChannel);

        return $salesChannel->getId();
    }

    private function getSalesChannelDomain(): SalesChannelDomainEntity
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('sales_channel_domain.repository');

        $salesChannelDomain = $repository->search(new Criteria(), $this->context)->first();
        static::assertInstanceOf(SalesChannelDomainEntity::class, $salesChannelDomain);

        return $salesChannelDomain;
    }

    private function getSalesChannelDomainId(): string
    {
        return $this->getSalesChannelDomain()->getId();
    }

    /**
     * @param array<string, string> $override
     */
    private function createTestEntity(array $override = []): string
    {
        $this->createProductStream();

        $id = Uuid::randomHex();
        $this->repository->upsert([
            array_merge([
                'id' => $id,
                'fileName' => 'Testexport.csv',
                'accessKey' => Uuid::randomHex(),
                'encoding' => ProductExportEntity::ENCODING_UTF8,
                'fileFormat' => ProductExportEntity::FILE_FORMAT_CSV,
                'interval' => 0,
                'headerTemplate' => 'name,stock',
                'bodyTemplate' => '{{ product.name }},{{ product.stock }}',
                'productStreamId' => '137b079935714281ba80b40f83f8d7eb',
                'storefrontSalesChannelId' => $this->getSalesChannelDomain()->getSalesChannelId(),
                'salesChannelId' => $this->getSalesChannelId(),
                'salesChannelDomainId' => $this->getSalesChannelDomainId(),
                'generateByCronjob' => false,
                'currencyId' => Defaults::CURRENCY,
            ], $override),
        ], $this->context);

        return $id;
    }

    private function createProductStream(): void
    {
        $connection = static::getContainer()->get(Connection::class);

        $randomProductIds = implode('|', \array_slice(array_column($this->createProducts(), 'id'), 0, 2));

        $connection->executeStatement("
            INSERT INTO `product_stream` (`id`, `api_filter`, `invalid`, `created_at`, `updated_at`)
            VALUES
                (UNHEX('137B079935714281BA80B40F83F8D7EB'), '[{\"type\": \"multi\", \"queries\": [{\"type\": \"multi\", \"queries\": [{\"type\": \"equalsAny\", \"field\": \"product.id\", \"value\": \"{$randomProductIds}\"}], \"operator\": \"AND\"}, {\"type\": \"multi\", \"queries\": [{\"type\": \"range\", \"field\": \"product.width\", \"parameters\": {\"gte\": 221, \"lte\": 932}}], \"operator\": \"AND\"}, {\"type\": \"multi\", \"queries\": [{\"type\": \"range\", \"field\": \"product.width\", \"parameters\": {\"lte\": 245}}], \"operator\": \"AND\"}, {\"type\": \"multi\", \"queries\": [{\"type\": \"equals\", \"field\": \"product.manufacturer.id\", \"value\": \"02f6b9aa385d4f40aaf573661b2cf919\"}, {\"type\": \"range\", \"field\": \"product.height\", \"parameters\": {\"gte\": 182}}], \"operator\": \"AND\"}], \"operator\": \"OR\"}]', 0, '2019-08-16 08:43:57.488', NULL);
        ");

        $connection->executeStatement("
            INSERT INTO `product_stream_filter` (`id`, `product_stream_id`, `parent_id`, `type`, `field`, `operator`, `value`, `parameters`, `position`, `custom_fields`, `created_at`, `updated_at`)
            VALUES
                (UNHEX('DA6CD9776BC84463B25D5B6210DDB57B'), UNHEX('137B079935714281BA80B40F83F8D7EB'), NULL, 'multi', NULL, 'OR', NULL, NULL, 0, NULL, '2019-08-16 08:43:57.469', NULL),
                (UNHEX('0EE60B6A87774E9884A832D601BE6B8F'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('DA6CD9776BC84463B25D5B6210DDB57B'), 'multi', NULL, 'AND', NULL, NULL, 1, NULL, '2019-08-16 08:43:57.478', NULL),
                (UNHEX('80B2B90171454467B769A4C161E74B87'), UNHEX('137B079935714281BA80B40F83F8D7EB'), UNHEX('0EE60B6A87774E9884A832D601BE6B8F'), 'equalsAny', 'id', NULL, '{$randomProductIds}', NULL, 1, NULL, '2019-08-16 08:43:57.480', NULL);
    ");
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function createProducts(): array
    {
        $productRepository = static::getContainer()->get('product.repository');
        $manufacturerId = Uuid::randomHex();
        $taxId = Uuid::randomHex();
        $salesChannelId = $this->getSalesChannelDomain()->getSalesChannelId();
        $products = [];

        for ($i = 0; $i < 10; ++$i) {
            $groupId = Uuid::randomHex();

            $products[] = [
                'id' => Uuid::randomHex(),
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'name' => 'Test',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['id' => $manufacturerId, 'name' => 'test'],
                'tax' => ['id' => $taxId, 'taxRate' => 17, 'name' => 'with id'],
                'visibilities' => [
                    ['salesChannelId' => $salesChannelId, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                ],
                'options' => [
                    [
                        'id' => Uuid::randomHex(),
                        'position' => 99,
                        'colorHexCode' => '#189eff',
                        'group' => [
                            'id' => $groupId,
                            'position' => 1,
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => [
                                    'name' => 'options-group',
                                    'description' => 'Default',
                                    'displayType' => 'Default',
                                    'sortingType' => 'Default',
                                ],
                            ],
                        ],
                        'translations' => [
                            Defaults::LANGUAGE_SYSTEM => [
                                'name' => 'Default',
                            ],
                        ],
                    ],
                    [
                        'id' => Uuid::randomHex(),
                        'position' => 98,
                        'colorHexCode' => '#ff0000',
                        'groupId' => $groupId,
                        'translations' => [
                            Defaults::LANGUAGE_SYSTEM => [
                                'name' => 'options-group',
                            ],
                        ],
                    ],
                ],
            ];
        }

        $productRepository->create($products, $this->context);

        return $products;
    }
}
