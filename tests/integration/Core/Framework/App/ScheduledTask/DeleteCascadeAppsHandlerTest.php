<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\ScheduledTask;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\App\ScheduledTask\DeleteCascadeAppsHandler;
use Cicada\Core\Framework\App\ScheduledTask\DeleteCascadeAppsTask;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[Group('skip-paratest')]
class DeleteCascadeAppsHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    private EntityRepository $scheduledTaskRepo;

    private EntityRepository $aclRoleRepo;

    private EntityRepository $integrationRepo;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->scheduledTaskRepo = static::getContainer()->get('scheduled_task.repository');
        $this->aclRoleRepo = static::getContainer()->get('acl_role.repository');
        $this->integrationRepo = static::getContainer()->get('integration.repository');
    }

    public function testCanDelete(): void
    {
        $timeExpired = (new \DateTimeImmutable())->modify('-1 day')->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $this->handleTask($timeExpired, 0);
    }

    public function testCannotDelete(): void
    {
        $timeExpired = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $this->handleTask($timeExpired, 1);
    }

    private function handleTask(string $timeExpired, int $numberEntities): void
    {
        $this->connection->executeStatement('DELETE FROM scheduled_task');
        $this->connection->executeStatement('DELETE FROM acl_role');
        $this->connection->executeStatement('DELETE FROM integration');

        $taskId = Uuid::randomHex();
        $originalNextExecution = (new \DateTime())->modify('-10 seconds');
        $interval = 300;

        $this->scheduledTaskRepo->create([
            [
                'id' => $taskId,
                'name' => 'test',
                'scheduledTaskClass' => DeleteCascadeAppsTask::class,
                'runInterval' => $interval,
                'defaultRunInterval' => $interval,
                'status' => ScheduledTaskDefinition::STATUS_QUEUED,
                'nextExecutionTime' => $originalNextExecution,
            ],
        ], Context::createDefaultContext());

        $this->aclRoleRepo->create([
            [
                'name' => 'SwagApp',
                'deletedAt' => $timeExpired,
                'integrations' => [
                    [
                        'label' => 'test',
                        'accessKey' => 'api access key',
                        'secretAccessKey' => 'test',
                        'deletedAt' => $timeExpired,
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        $task = new DeleteCascadeAppsTask();
        $task->setTaskId($taskId);

        $handler = new DeleteCascadeAppsHandler(
            $this->scheduledTaskRepo,
            $this->createMock(LoggerInterface::class),
            $this->aclRoleRepo,
            $this->integrationRepo
        );

        $handler($task);

        $aclRoles = $this->aclRoleRepo->search(new Criteria(), Context::createDefaultContext())->getEntities();
        static::assertCount($numberEntities, $aclRoles);

        $integrations = $this->integrationRepo->search(new Criteria(), Context::createDefaultContext())->getEntities();
        static::assertCount($numberEntities, $integrations);
    }
}
