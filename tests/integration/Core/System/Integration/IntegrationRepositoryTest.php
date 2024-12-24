<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Integration;

use Cicada\Core\Framework\Api\Util\AccessKeyHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Integration\IntegrationCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class IntegrationRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<IntegrationCollection>
     */
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('integration.repository');
    }

    public function testCreationWithAccessKeys(): void
    {
        $id = Uuid::randomHex();

        $records = [
            [
                'id' => $id,
                'label' => 'My app',
                'accessKey' => AccessKeyHelper::generateAccessKey('integration'),
                'secretAccessKey' => AccessKeyHelper::generateSecretAccessKey(),
            ],
        ];

        $context = Context::createDefaultContext();

        $this->repository->create($records, $context);

        $entities = $this->repository->search(new Criteria([$id]), $context);
        $entity = $entities
            ->getEntities()
            ->first();

        static::assertNotNull($entity);
        static::assertEquals(1, $entities->count());
        static::assertEquals('My app', $entity->getLabel());
    }

    public function testCreationAdminDefaultsToFalse(): void
    {
        $id = Uuid::randomHex();

        $records = [
            [
                'id' => $id,
                'label' => 'My app',
                'accessKey' => AccessKeyHelper::generateAccessKey('integration'),
                'secretAccessKey' => AccessKeyHelper::generateSecretAccessKey(),
            ],
        ];

        $context = Context::createDefaultContext();

        $this->repository->create($records, $context);

        $entities = $this->repository->search(new Criteria([$id]), $context);
        $entity = $entities
            ->getEntities()
            ->first();

        static::assertNotNull($entity);
        static::assertEquals(1, $entities->count());
        static::assertEquals('My app', $entity->getLabel());
        static::assertFalse($entity->getAdmin());
    }

    public function testCreationWithAdminRole(): void
    {
        $id = Uuid::randomHex();

        $records = [
            [
                'id' => $id,
                'label' => 'My app',
                'accessKey' => AccessKeyHelper::generateAccessKey('integration'),
                'secretAccessKey' => AccessKeyHelper::generateSecretAccessKey(),
                'admin' => true,
            ],
        ];

        $context = Context::createDefaultContext();

        $this->repository->create($records, $context);

        $entities = $this->repository->search(new Criteria([$id]), $context);
        $entity = $entities
            ->getEntities()
            ->first();

        static::assertNotNull($entity);
        static::assertEquals(1, $entities->count());
        static::assertEquals('My app', $entity->getLabel());
        static::assertTrue($entity->getAdmin());
    }
}
