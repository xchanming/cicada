<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Command;

use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\Command\DeactivateAppCommand;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\AppSystemTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class DeactivateAppCommandTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<AppCollection>
     */
    private EntityRepository $appRepository;

    protected function setUp(): void
    {
        $this->appRepository = static::getContainer()->get('app.repository');
    }

    public function testDeactivateApp(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/withoutPermissions');
        $appName = 'withoutPermissions';
        $commandTester = new CommandTester(static::getContainer()->get(DeactivateAppCommand::class));

        $commandTester->execute(['name' => $appName]);

        static::assertSame(0, $commandTester->getStatusCode());

        static::assertStringContainsString('[OK] App deactivated successfully.', $commandTester->getDisplay());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $appName));

        $app = $this->appRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($app);

        static::assertFalse($app->isActive());
    }

    public function testDeactivateNonExistingAppFails(): void
    {
        $commandTester = new CommandTester(static::getContainer()->get(DeactivateAppCommand::class));

        $appName = 'NonExisting';
        $commandTester->execute(['name' => $appName]);

        static::assertSame(1, $commandTester->getStatusCode());

        static::assertStringContainsString("[ERROR] No app found for \"$appName\".", $commandTester->getDisplay());
    }
}
