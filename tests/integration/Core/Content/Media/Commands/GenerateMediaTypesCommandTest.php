<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Commands;

use Cicada\Core\Content\Media\Commands\GenerateMediaTypesCommand;
use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Content\Media\MediaType\MediaType;
use Cicada\Core\Content\Test\Media\MediaFixtures;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class GenerateMediaTypesCommandTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MediaFixtures;

    /**
     * @var EntityRepository<MediaCollection>
     */
    private EntityRepository $mediaRepository;

    private GenerateMediaTypesCommand $generateMediaTypesCommand;

    private Context $context;

    /**
     * @var array<string>
     */
    private array $initialMediaIds;

    protected function setUp(): void
    {
        $this->mediaRepository = static::getContainer()->get('media.repository');

        $this->generateMediaTypesCommand = static::getContainer()->get(GenerateMediaTypesCommand::class);

        $this->context = Context::createDefaultContext();

        /** @var array<string> $ids */
        $ids = $this->mediaRepository->searchIds(new Criteria(), $this->context)->getIds();
        $this->initialMediaIds = $ids;
    }

    public function testExecuteHappyPath(): void
    {
        $this->createValidMediaFiles();

        $commandTester = new CommandTester($this->generateMediaTypesCommand);
        $commandTester->execute([]);

        $mediaResult = $this->getNewMediaEntities();
        /** @var MediaEntity $updatedMedia */
        foreach ($mediaResult as $updatedMedia) {
            static::assertInstanceOf(MediaType::class, $updatedMedia->getMediaType());
        }
    }

    public function testExecuteWithCustomBatchSize(): void
    {
        $this->createValidMediaFiles();

        $commandTester = new CommandTester($this->generateMediaTypesCommand);
        $commandTester->execute([]);

        $searchCriteria = new Criteria();
        $mediaResult = $this->mediaRepository->search($searchCriteria, $this->context);
        /** @var MediaEntity $updatedMedia */
        foreach ($mediaResult->getEntities() as $updatedMedia) {
            static::assertInstanceOf(MediaType::class, $updatedMedia->getMediaType());
        }
    }

    public function testExecuteWithMediaWithoutFile(): void
    {
        $this->setFixtureContext($this->context);
        $this->getEmptyMedia();

        $commandTester = new CommandTester($this->generateMediaTypesCommand);
        $commandTester->execute([]);

        $mediaResult = $this->getNewMediaEntities();
        /** @var MediaEntity $updatedMedia */
        foreach ($mediaResult as $updatedMedia) {
            static::assertNull($updatedMedia->getMediaType());
        }
    }

    public function testExecuteThrowsExceptionOnInvalidBatchSize(): void
    {
        $this->expectException(MediaException::class);
        $this->expectExceptionMessage('Provided batch size is invalid.');

        $this->createValidMediaFiles();

        $commandTester = new CommandTester($this->generateMediaTypesCommand);
        $commandTester->execute(['-b' => 'test']);
    }

    protected function createValidMediaFiles(): void
    {
        $this->setFixtureContext($this->context);
        $mediaPng = $this->getPng();
        $mediaJpg = $this->getJpg();
        $mediaPdf = $this->getPdf();

        $this->mediaRepository->upsert([
            [
                'id' => $mediaPng->getId(),
                'type' => null,
            ],
            [
                'id' => $mediaJpg->getId(),
                'type' => null,
            ],
            [
                'id' => $mediaPdf->getId(),
                'type' => null,
            ],
        ], $this->context);

        $filePath = $mediaPng->getPath();

        $this->getPublicFilesystem()->writeStream(
            $filePath,
            fopen(__DIR__ . '/../fixtures/cicada-logo.png', 'r')
        );

        $filePath = $mediaJpg->getPath();

        $this->getPublicFilesystem()->writeStream(
            $filePath,
            fopen(__DIR__ . '/../fixtures/cicada.jpg', 'r')
        );

        $filePath = $mediaPdf->getPath();

        $this->getPublicFilesystem()->writeStream(
            $filePath,
            fopen(__DIR__ . '/../fixtures/small.pdf', 'r')
        );
    }

    private function getNewMediaEntities(): MediaCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $this->initialMediaIds));
        $result = $this->mediaRepository->searchIds($criteria, $this->context);
        static::assertEquals(\count($this->initialMediaIds), $result->getTotal());

        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [
                new EqualsAnyFilter('id', $this->initialMediaIds),
            ]
        ));

        $entities = $this->mediaRepository->search($criteria, $this->context)->getEntities();
        static::assertInstanceOf(MediaCollection::class, $entities);

        return $entities;
    }
}
