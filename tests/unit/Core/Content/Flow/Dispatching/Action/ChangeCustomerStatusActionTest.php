<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Content\Flow\Dispatching\Action\ChangeCustomerStatusAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(ChangeCustomerStatusAction::class)]
class ChangeCustomerStatusActionTest extends TestCase
{
    private MockObject&EntityRepository $repository;

    private ChangeCustomerStatusAction $action;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->action = new ChangeCustomerStatusAction($this->repository);
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
        static::assertSame('action.change.customer.status', ChangeCustomerStatusAction::getName());
    }

    public function testActionExecuted(): void
    {
        $customerId = Uuid::randomHex();
        $flow = new StorableFlow('foo', Context::createDefaultContext(), [], [
            CustomerAware::CUSTOMER_ID => $customerId,
        ]);
        $flow->setConfig(['active' => true]);

        $this->repository->expects(static::once())
            ->method('update')
            ->with([['id' => $customerId, 'active' => true]]);

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
