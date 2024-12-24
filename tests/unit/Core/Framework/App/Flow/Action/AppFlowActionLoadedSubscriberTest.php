<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Flow\Action;

use Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionDefinition;
use Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionEntity;
use Cicada\Core\Framework\App\Flow\Action\AppFlowActionLoadedSubscriber;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AppFlowActionLoadedSubscriber::class)]
class AppFlowActionLoadedSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        static::assertEquals([
            'app_flow_action.loaded' => 'unserialize',
        ], AppFlowActionLoadedSubscriber::getSubscribedEvents());
    }

    public function testUnserialize(): void
    {
        $idFlowAction = Uuid::randomHex();

        $appFlowAction = new AppFlowActionEntity();
        $appFlowAction->setId($idFlowAction);
        $iconPath = __DIR__ . '/../../Manifest/_fixtures/icon.png';

        $fileIcon = '';
        if (file_exists($iconPath)) {
            $fileIcon = \file_get_contents($iconPath);
        }

        $appFlowAction->setIconRaw($fileIcon !== false ? $fileIcon : null);

        $subscriber = new AppFlowActionLoadedSubscriber();
        $event = new EntityLoadedEvent(new AppFlowActionDefinition(), [$appFlowAction], Context::createDefaultContext());

        $subscriber->unserialize($event);
        static::assertNotFalse($fileIcon);

        static::assertEquals(
            base64_encode($fileIcon),
            $appFlowAction->getIcon()
        );
    }
}
