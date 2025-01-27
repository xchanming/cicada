<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Api;

use Cicada\Core\Content\Flow\Api\FlowActionCollector;
use Cicada\Core\Content\Flow\Api\FlowActionDefinition;
use Cicada\Core\Content\Flow\Dispatching\Action\AddCustomerTagAction;
use Cicada\Core\Content\Flow\Dispatching\Action\RemoveOrderTagAction;
use Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(FlowActionCollector::class)]
class FlowActionCollectorTest extends TestCase
{
    public function testCollect(): void
    {
        $addCustomerTag = new AddCustomerTagAction($this->createMock(EntityRepository::class));
        $removeOrderTag = new RemoveOrderTagAction($this->createMock(EntityRepository::class));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(static::once())->method('dispatch');

        $appFlowActionRepo = $this->createMock(EntityRepository::class);
        $entitySearchResult = $this->createMock(EntitySearchResult::class);
        $entitySearchResult->expects(static::once())
            ->method('getEntities')
            ->willReturn(new EntityCollection([
                (new AppFlowActionEntity())->assign([
                    'id' => Uuid::randomHex(),
                    'name' => 'slack.app',
                    'requirements' => ['orderAware'],
                    'delayable' => false,
                ]),
            ]));

        $appFlowActionRepo->expects(static::once())
            ->method('search')
            ->willReturn($entitySearchResult);

        $flowActionCollector = new FlowActionCollector(
            [$addCustomerTag, $removeOrderTag],
            $eventDispatcher,
            $appFlowActionRepo
        );

        $result = $flowActionCollector->collect(Context::createDefaultContext());

        $customerRequirements = [];
        $customerRequirements[] = 'customerAware';

        $orderRequirements = [];
        $orderRequirements[] = 'orderAware';

        static::assertEquals(
            [
                AddCustomerTagAction::getName() => new FlowActionDefinition(
                    AddCustomerTagAction::getName(),
                    $customerRequirements,
                    true
                ),
                RemoveOrderTagAction::getName() => new FlowActionDefinition(
                    RemoveOrderTagAction::getName(),
                    $orderRequirements,
                    true
                ),
                'slack.app' => new FlowActionDefinition(
                    'slack.app',
                    ['orderAware'],
                    false
                ),
            ],
            $result->getElements()
        );
    }
}
