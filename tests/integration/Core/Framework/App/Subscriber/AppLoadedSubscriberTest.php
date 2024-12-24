<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Subscriber;

use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\Subscriber\AppLoadedSubscriber;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AppLoadedSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([
            'app.loaded' => 'unserialize',
        ], AppLoadedSubscriber::getSubscribedEvents());
    }

    public function testUnserialize(): void
    {
        /** @var EntityRepository<AppCollection> $appRepository */
        $appRepository = static::getContainer()->get('app.repository');

        $id = Uuid::randomHex();

        $appRepository->create([
            [
                'id' => $id,
                'name' => 'App',
                'path' => __DIR__ . '/../Manifest/_fixtures/test',
                'version' => '0.0.1',
                'label' => 'test App',
                'accessToken' => 'test',
                'iconRaw' => file_get_contents(__DIR__ . '/../Manifest/_fixtures/test/icon.png'),
                'integration' => [
                    'label' => 'App1',
                    'accessKey' => 'test',
                    'secretAccessKey' => 'test',
                ],
                'aclRole' => [
                    'name' => 'App1',
                ],
            ],
        ], Context::createDefaultContext());

        $app = $appRepository->search(new Criteria([$id]), Context::createDefaultContext())->getEntities()->get($id);
        static::assertNotNull($app);
        $icon = \file_get_contents(__DIR__ . '/../Manifest/_fixtures/test/icon.png');
        static::assertNotFalse($icon);

        static::assertSame(\base64_encode($icon), $app->getIcon());
    }
}
