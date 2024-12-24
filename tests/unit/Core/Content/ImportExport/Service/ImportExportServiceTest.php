<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ImportExport\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogCollection;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogDefinition;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Cicada\Core\Content\ImportExport\Exception\ProfileWrongTypeException;
use Cicada\Core\Content\ImportExport\ImportExportException;
use Cicada\Core\Content\ImportExport\ImportExportProfileDefinition;
use Cicada\Core\Content\ImportExport\ImportExportProfileEntity;
use Cicada\Core\Content\ImportExport\Service\FileService;
use Cicada\Core\Content\ImportExport\Service\ImportExportService;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\System\User\UserDefinition;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

/**
 * @internal
 */
#[CoversClass(ImportExportService::class)]
class ImportExportServiceTest extends TestCase
{
    public function testPrepareExportWithImportOnlyProfileThrowsException(): void
    {
        $profileId = Uuid::randomHex();

        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectException(ProfileWrongTypeException::class);
        } else {
            $this->expectException(ImportExportException::class);
        }
        $this->expectExceptionMessage(\sprintf('The import/export profile with id %s can only be used for import', $profileId));

        $this->createImportExportService($profileId)->prepareExport(
            Context::createDefaultContext(),
            $profileId,
            new \DateTimeImmutable(),
        );
    }

    public function testPrepareExportWithImportOnlyProfileDoesNotThrowExceptionIfInvalidRecordsShouldBeExported(): void
    {
        $profileId = Uuid::randomHex();

        $log = $this->createImportExportService($profileId)->prepareExport(
            Context::createDefaultContext(),
            $profileId,
            new \DateTimeImmutable(),
            activity: ImportExportLogEntity::ACTIVITY_INVALID_RECORDS_EXPORT
        );

        static::assertSame($profileId, $log->getProfileId());
        static::assertSame('Test Profile', $log->getProfileName());
    }

    private function createImportExportService(string $profileId): ImportExportService
    {
        $profile = new ImportExportProfileEntity();
        $profile->setId($profileId);
        $profile->setUniqueIdentifier($profileId);
        $profile->setType(ImportExportProfileEntity::TYPE_IMPORT);
        $profile->setTranslated(['label' => 'Test Profile']);
        $profile->setConfig([]);
        $profile->setSourceEntity(ProductDefinition::ENTITY_NAME);
        $profile->setFileType('text/csv');

        /** @var StaticEntityRepository<ImportExportLogCollection> $logRepo */
        $logRepo = new StaticEntityRepository([], new ImportExportLogDefinition());

        /** @var StaticEntityRepository<UserCollection> */
        $userRepo = new StaticEntityRepository([], new UserDefinition());

        /** @var StaticEntityRepository<EntityCollection<ImportExportProfileEntity>> $profileRepo */
        $profileRepo = new StaticEntityRepository([new EntityCollection([$profile])], new ImportExportProfileDefinition());

        return new ImportExportService(
            $logRepo,
            $userRepo,
            $profileRepo,
            $this->createMock(FileService::class),
        );
    }
}
