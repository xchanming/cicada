<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Flow\FlowAction;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionCollection;
use Cicada\Core\Framework\App\Flow\Action\AppFlowActionLoadedSubscriber;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class AppFlowActionLoadedSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([
            'app_flow_action.loaded' => 'unserialize',
        ], AppFlowActionLoadedSubscriber::getSubscribedEvents());
    }

    public function testUnserialize(): void
    {
        /** @var EntityRepository<AppFlowActionCollection> $appFlowActionRepository */
        $appFlowActionRepository = static::getContainer()->get('app_flow_action.repository');

        $idFlowAction = $this->registerFlowAction();

        $appFlowAction = $appFlowActionRepository->search(new Criteria([$idFlowAction]), Context::createDefaultContext())->getEntities()->get($idFlowAction);
        static::assertNotNull($appFlowAction);

        $icon = \file_get_contents(__DIR__ . '/../../Manifest/_fixtures/test/icon.png');
        static::assertNotFalse($icon);

        static::assertSame(
            base64_encode($icon),
            $appFlowAction->getIcon()
        );
    }

    private function registerFlowAction(): string
    {
        $appRepository = static::getContainer()->get('app.repository');

        $idFlowAction = Uuid::randomHex();

        $appRepository->create([
            [
                'id' => Uuid::randomHex(),
                'name' => 'App',
                'path' => __DIR__ . '/../Manifest/_fixtures/test',
                'version' => '0.0.1',
                'label' => 'test App',
                'accessToken' => 'test',
                'integration' => [
                    'label' => 'App1',
                    'accessKey' => 'test',
                    'secretAccessKey' => 'test',
                ],
                'aclRole' => [
                    'name' => 'App1',
                ],
                'flowActions' => [
                    [
                        'id' => $idFlowAction,
                        'name' => 'FlowActiontest',
                        'sign' => 'Test',
                        'label' => 'Flow Action test',
                        'iconRaw' => file_get_contents(__DIR__ . '/../../Manifest/_fixtures/test/icon.png'),
                        'url' => 'http://xxxxx',
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        return $idFlowAction;
    }
}
