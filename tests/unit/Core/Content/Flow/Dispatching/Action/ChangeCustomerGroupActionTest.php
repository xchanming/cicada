<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Flow\Dispatching\Action\ChangeCustomerGroupAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(ChangeCustomerGroupAction::class)]
class ChangeCustomerGroupActionTest extends TestCase
{
    private MockObject&EntityRepository $repository;

    private ChangeCustomerGroupAction $action;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->action = new ChangeCustomerGroupAction($this->repository);
    }

    public function testRequirements(): void
    {
        static::assertSame(
            [CustomerAware::class],
            $this->action->requirements()
        );
    }

    public function testName(): void
    {
        static::assertSame('action.change.customer.group', ChangeCustomerGroupAction::getName());
    }

    public function testActionExecuted(): void
    {
        $groupId = Uuid::randomHex();
        $customerId = Uuid::randomHex();

        $flow = new StorableFlow('foo', Context::createDefaultContext(), [], [
            CustomerAware::CUSTOMER_ID => $customerId,
        ]);
        $flow->setConfig(['customerGroupId' => $groupId]);

        $this->repository->expects(static::once())
            ->method('update')
            ->with([['id' => $customerId, 'groupId' => $groupId]]);

        $this->action->handleFlow($flow);
    }

    public function testActionWithNotAware(): void
    {
        $flow = new StorableFlow('foo', Context::createDefaultContext());

        $this->repository->expects(static::never())->method('update');

        $this->action->handleFlow($flow);
    }

    public function testActionWithEmptyConfig(): void
    {
        $flow = new StorableFlow('foo', Context::createDefaultContext(), [], [
            CustomerAware::CUSTOMER_ID => Uuid::randomHex(),
        ]);

        $this->repository->expects(static::never())->method('update');

        $this->action->handleFlow($flow);
    }
}
