<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogCollection;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Content\ImportExport\ImportExport;
use Cicada\Core\Content\ImportExport\ImportExportFactory;
use Cicada\Core\Content\ImportExport\ImportExportProfileEntity;
use Cicada\Core\Content\ImportExport\Processing\Mapping\Mapping;
use Cicada\Core\Content\ImportExport\Processing\Pipe\PipeFactory;
use Cicada\Core\Content\ImportExport\Processing\Reader\CsvReader;
use Cicada\Core\Content\ImportExport\Processing\Reader\CsvReaderFactory;
use Cicada\Core\Content\ImportExport\Processing\Writer\CsvFileWriterFactory;
use Cicada\Core\Content\ImportExport\Service\FileService;
use Cicada\Core\Content\ImportExport\Service\ImportExportService;
use Cicada\Core\Content\ImportExport\Strategy\Import\OneByOneImportStrategy;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\ImportExport\Struct\Progress;
use Cicada\Core\Content\Media\File\FileSaver;
use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Test\ImportExport\MockRepository;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\CloneBehavior;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\FilesystemBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\RequestStackTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SessionTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
abstract class AbstractImportExportTestCase extends TestCase
{
    use CacheTestBehaviour;
    use DatabaseTransactionBehaviour;
    use FilesystemBehaviour;
    use KernelTestBehaviour;
    use RequestStackTestBehaviour;
    use SalesChannelApiTestBehaviour;
    use SessionTestBehaviour;

    final public const TEST_IMAGE = __DIR__ . '/fixtures/cicada-logo.png';

    /**
     * @var EntityRepository<ProductCollection>
     */
    protected EntityRepository $productRepository;

    protected TraceableEventDispatcher $listener;

    protected function setUp(): void
    {
        $this->productRepository = static::getContainer()->get('product.repository');

        $this->listener = static::getContainer()->get(EventDispatcherInterface::class);
    }

    /**
     * @param array<array<string, mixed>> $invalidLog
     */
    public static function assertImportExportSucceeded(Progress $progress, array $invalidLog = []): void
    {
        static::assertSame(Progress::STATE_SUCCEEDED, $progress->getState(), json_encode($invalidLog, \JSON_THROW_ON_ERROR));
    }

    public static function assertImportExportFailed(Progress $progress): void
    {
        static::assertSame(Progress::STATE_FAILED, $progress->getState());
    }

    /**
     * @param array<string, bool> $configOverrides
     */
    protected function runCustomerImportWithConfigAndMockedRepository(array $configOverrides): MockRepository
    {
        $context = Context::createDefaultContext();
        $context->addState(EntityIndexerRegistry::DISABLE_INDEXING);

        $importExportService = static::getContainer()->get(ImportExportService::class);
        $expireDate = new \DateTimeImmutable('2099-01-01');

        // setup profile
        $clonedCustomerProfile = $this->cloneDefaultProfile(CustomerDefinition::ENTITY_NAME);
        $config = array_merge($clonedCustomerProfile->getConfig(), $configOverrides);
        $this->updateProfileConfig($clonedCustomerProfile->getId(), $config);

        $file = new UploadedFile(__DIR__ . '/fixtures/customers.csv', 'customers_used_with_config.csv', 'text/csv');
        $logEntity = $importExportService->prepareImport(
            $context,
            $clonedCustomerProfile->getId(),
            $expireDate,
            $file
        );

        $progress = new Progress($logEntity->getId(), Progress::STATE_PROGRESS, 0, null);

        $pipeFactory = static::getContainer()->get(PipeFactory::class);
        $readerFactory = static::getContainer()->get(CsvReaderFactory::class);
        $writerFactory = static::getContainer()->get(CsvFileWriterFactory::class);
        $eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);

        $mockRepository = new MockRepository(static::getContainer()->get(CustomerDefinition::class));

        $importExport = new ImportExport(
            $importExportService,
            $logEntity,
            static::getContainer()->get('cicada.filesystem.private'),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get(Connection::class),
            $mockRepository,
            $pipeFactory->create($logEntity),
            $readerFactory->create($logEntity),
            $writerFactory->create($logEntity),
            static::getContainer()->get(FileService::class),
            new OneByOneImportStrategy($eventDispatcher, $mockRepository),
            5,
            5
        );

        do {
            $progress = $importExport->import($context, $progress->getOffset());
        } while (!$progress->isFinished());

        static::assertSame(Progress::STATE_SUCCEEDED, $progress->getState(), 'Import with MockRepository failed. Maybe check for mock errors.');

        return $mockRepository;
    }

    protected function createProduct(?string $productId = null): string
    {
        $productId ??= Uuid::randomHex();

        $data = [
            'id' => $productId,
            'name' => 'test',
            'productNumber' => Uuid::randomHex(),
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false],
            ],
            'active' => true,
            'tax' => ['name' => 'test', 'taxRate' => 15],
        ];
        static::getContainer()->get('product.repository')->create([$data], Context::createDefaultContext());

        return $productId;
    }

    /**
     * @param array<string, string> $promotionOverride
     *
     * @return array<string, mixed>
     */
    protected function createPromotion(array $promotionOverride = []): array
    {
        $promotion = array_merge([
            'id' => $promotionOverride['id'] ?? Uuid::randomHex(),
            'name' => 'Test case promotion',
            'active' => true,
            'useIndividualCodes' => true,
        ], $promotionOverride);

        static::getContainer()->get('promotion.repository')->upsert([$promotion], Context::createDefaultContext());

        return $promotion;
    }

    /**
     * @param array<string, string> $promotionCodeOverride
     *
     * @return array<string, mixed>
     */
    protected function createPromotionCode(string $promotionId, array $promotionCodeOverride = []): array
    {
        $promotionCode = array_merge([
            'id' => $promotionCodeOverride['id'] ?? Uuid::randomHex(),
            'promotionId' => $promotionId,
            'code' => 'TestCode',
        ], $promotionCodeOverride);

        static::getContainer()->get('promotion_individual_code.repository')->upsert([$promotionCode], Context::createDefaultContext());

        return $promotionCode;
    }

    protected function createRule(?string $ruleId = null): string
    {
        $ruleId ??= Uuid::randomHex();
        static::getContainer()->get('rule.repository')->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            Context::createDefaultContext()
        );

        return $ruleId;
    }

    protected function getDefaultProfileId(string $entity): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('systemDefault', true));
        $criteria->addFilter(new EqualsFilter('sourceEntity', $entity));

        $id = static::getContainer()->get('import_export_profile.repository')->searchIds($criteria, Context::createDefaultContext())->firstId();
        static::assertNotNull($id);

        return $id;
    }

    protected function cloneDefaultProfile(string $entity): ImportExportProfileEntity
    {
        /** @var EntityRepository<EntityCollection<ImportExportProfileEntity>> $profileRepository */
        $profileRepository = static::getContainer()->get('import_export_profile.repository');

        $systemDefaultProfileId = $this->getDefaultProfileId($entity);
        $newId = Uuid::randomHex();

        $profileRepository->clone(
            $systemDefaultProfileId,
            Context::createDefaultContext(),
            $newId,
            new CloneBehavior(['technicalName' => uniqid('technical_name_')])
        );

        // get the cloned profile
        $profile = $profileRepository->search(new Criteria([$newId]), Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($profile);

        return $profile;
    }

    /**
     * @param list<array{key: string, mappedKey: string}>|array<Mapping> $mappings
     */
    protected function updateProfileMapping(string $profileId, array $mappings): void
    {
        static::getContainer()->get('import_export_profile.repository')->update([
            [
                'id' => $profileId,
                'mapping' => $mappings,
            ],
        ], Context::createDefaultContext());
    }

    /**
     * @param array<array<string, string>> $updateBy
     */
    protected function updateProfileUpdateBy(string $profileId, array $updateBy): void
    {
        static::getContainer()->get('import_export_profile.repository')->update([
            [
                'id' => $profileId,
                'updateBy' => $updateBy,
            ],
        ], Context::createDefaultContext());
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function updateProfileConfig(string $profileId, array $config): void
    {
        static::getContainer()->get('import_export_profile.repository')->update([
            [
                'id' => $profileId,
                'config' => $config,
            ],
        ], Context::createDefaultContext());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTestProduct(string $id): array
    {
        $manufacturerId = Uuid::randomHex();
        $catId1 = Uuid::randomHex();
        $catId2 = Uuid::randomHex();
        $taxId = Uuid::randomHex();

        static::getContainer()->get('product_manufacturer.repository')->upsert([
            ['id' => $manufacturerId, 'name' => 'test'],
        ], Context::createDefaultContext());

        static::getContainer()->get('category.repository')->upsert([
            ['id' => $catId1, 'name' => 'test'],
            ['id' => $catId2, 'name' => 'bar'],
        ], Context::createDefaultContext());

        static::getContainer()->get('tax.repository')->upsert([
            ['id' => $taxId, 'name' => 'test', 'taxRate' => 15],
        ], Context::createDefaultContext());

        $tempFile = tempnam(sys_get_temp_dir(), '');
        static::assertIsString($tempFile);
        copy(self::TEST_IMAGE, $tempFile);

        $fileSize = filesize($tempFile);
        static::assertIsInt($fileSize);
        $mediaFile = new MediaFile($tempFile, 'image/png', 'png', $fileSize);

        $mediaId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        static::getContainer()->get('media.repository')->create(
            [
                [
                    'id' => $mediaId,
                ],
            ],
            $context
        );

        try {
            static::getContainer()->get(FileSaver::class)->persistFileToMedia(
                $mediaFile,
                'test-file',
                $mediaId,
                $context
            );
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        $scA = $this->createSalesChannel([
            'domains' => [[
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'http://localhost.test/a',
            ]],
        ]);
        $scB = $this->createSalesChannel([
            'domains' => [[
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'http://localhost.test/b',
            ]],
        ]);
        $scC = $this->createSalesChannel([
            'domains' => [[
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'http://localhost.test/c',
            ]],
        ]);

        $data = [
            'id' => $id,
            'versionId' => Defaults::LIVE_VERSION,
            'parentVersionId' => '0fa91ce3e96a4bc2be4bd9ce752c3425',
            'productNumber' => Uuid::randomHex(),
            'stock' => 10,
            'price' => [
                Defaults::CURRENCY => [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 15,
                    'net' => 10,
                    'linked' => false,
                    'listPrice' => null,
                    'extensions' => [],
                ],
            ],
            'cover' => ['mediaId' => $mediaId],
            'manufacturerId' => $manufacturerId,
            'productManufacturerVersionId' => '0fa91ce3e96a4bc2be4bd9ce752c3425',
            'taxId' => $taxId,
            'categories' => [
                [
                    'id' => $catId1,
                ],
                [
                    'id' => $catId2,
                ],
            ],
            'active' => false,
            'isCloseout' => false,
            'markAsTopseller' => false,
            'maxPurchase' => 0,
            'minPurchase' => 1,
            'purchaseSteps' => 1,
            'restockTime' => 3,
            'shippingFree' => false,
            'releaseDate' => $this->atomDate(),
            'createdAt' => $this->atomDate(),
            'translations' => [
                'en-GB' => [
                    'name' => 'Default name',
                ],
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'Default name',
                ],
                'zh-CN' => [
                    'name' => 'German',
                    'description' => 'Beschreibung',
                ],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
                [
                    'salesChannelId' => $scA['id'],
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
                [
                    'salesChannelId' => $scB['id'],
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_SEARCH,
                ],
                [
                    'salesChannelId' => $scC['id'],
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_LINK,
                ],
            ],
        ];
        $this->productRepository->create([$data], Context::createDefaultContext());

        return $data;
    }

    protected function atomDate(string $str = 'now'): \DateTimeInterface
    {
        return new \DateTimeImmutable((new \DateTimeImmutable($str))->format(\DateTime::ATOM));
    }

    protected function import(
        Context $context,
        string $entityName,
        string $path,
        string $originalName,
        ?string $profileId = null,
        bool $dryRun = false,
        bool $absolutePath = false,
        bool $useBatchImport = false
    ): Progress {
        $factory = static::getContainer()->get(ImportExportFactory::class);

        $importExportService = static::getContainer()->get(ImportExportService::class);

        $profileId ??= $this->getDefaultProfileId($entityName);

        $expireDate = new \DateTimeImmutable('2099-01-01');
        $file = new UploadedFile((!$absolutePath ? __DIR__ : '') . $path, $originalName, 'text/csv');

        $logEntity = $importExportService->prepareImport(
            $context,
            $profileId,
            $expireDate,
            $file,
            [],
            $dryRun
        );

        $progress = new Progress($logEntity->getId(), Progress::STATE_PROGRESS, 0, null);
        do {
            $progress = $importExportService->getProgress($logEntity->getId(), $progress->getOffset());
            $importExport = $factory->create($logEntity->getId(), 5, 5, $useBatchImport);
            $progress = $importExport->import($context, $progress->getOffset());
        } while (!$progress->isFinished());

        return $progress;
    }

    protected function export(Context $context, string $entityName, ?Criteria $criteria = null, ?int $groupSize = null, ?string $profileId = null): Progress
    {
        $factory = static::getContainer()->get(ImportExportFactory::class);

        $importExportService = static::getContainer()->get(ImportExportService::class);

        $profileId ??= $this->getDefaultProfileId($entityName);

        $expireDate = new \DateTimeImmutable('2099-01-01');
        $logEntity = $importExportService->prepareExport($context, $profileId, $expireDate);

        $progress = new Progress($logEntity->getId(), Progress::STATE_PROGRESS, 0, null);
        do {
            $groupSize = $groupSize ? $groupSize - 1 : 0;
            $criteria ??= new Criteria();
            $importExport = $factory->create($logEntity->getId(), $groupSize, $groupSize);
            $progress = $importExport->export(Context::createDefaultContext(), $criteria, $progress->getOffset());
        } while (!$progress->isFinished());

        return $progress;
    }

    protected function getLogEntity(string $logId): ImportExportLogEntity
    {
        $criteria = new Criteria([$logId]);
        $criteria->addAssociation('profile');
        $criteria->addAssociation('file');

        /** @var EntityRepository<ImportExportLogCollection> $importExportLogRepo */
        $importExportLogRepo = static::getContainer()->get('import_export_log.repository');

        $log = $importExportLogRepo->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($log);

        return $log;
    }

    /**
     * @return array<array<string, mixed>>
     */
    protected function getInvalidLogContent(?string $invalidLogId): array
    {
        if (!$invalidLogId) {
            return [];
        }

        $logEntity = $this->getLogEntity($invalidLogId);
        $config = Config::fromLog($logEntity);
        $reader = new CsvReader();
        $filesystem = static::getContainer()->get('cicada.filesystem.private');

        $file = $logEntity->getFile();
        static::assertNotNull($file);
        $resource = $filesystem->readStream($file->getPath());
        $log = $reader->read($config, $resource, 0);

        return $log instanceof \Traversable ? iterator_to_array($log) : [];
    }

    /**
     * @param array<array<string, string>> $customFields
     */
    protected function createCustomField(array $customFields, string $entityName): void
    {
        $repo = static::getContainer()->get('custom_field_set.repository');

        $attributeSet = [
            'name' => 'test_set',
            'config' => ['description' => 'test'],
            'customFields' => $customFields,
            'relations' => [
                [
                    'entityName' => $entityName,
                ],
            ],
        ];

        $repo->create([$attributeSet], Context::createDefaultContext());
    }
}
