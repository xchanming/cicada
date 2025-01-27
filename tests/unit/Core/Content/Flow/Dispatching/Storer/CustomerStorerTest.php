<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Cicada\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\CustomerStorer;
use Cicada\Core\Content\Flow\Events\BeforeLoadStorableFlowDataEvent;
use Cicada\Core\Content\Flow\Exception\CustomerDeletedException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\User\Recovery\UserRecoveryRequestEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(CustomerStorer::class)]
class CustomerStorerTest extends TestCase
{
    private CustomerStorer $storer;

    private MockObject&EntityRepository $repository;

    private MockObject&EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->storer = new CustomerStorer($this->repository, $this->dispatcher);
    }

    public function testStoreWithAware(): void
    {
        $event = $this->createMock(CustomerRegisterEvent::class);
        $stored = [];
        $stored = $this->storer->store($event, $stored);
        static::assertArrayHasKey(CustomerAware::CUSTOMER_ID, $stored);
    }

    public function testStoreWillCatchCustomerDeleteException(): void
    {
        $event = $this->createMock(OrderStateMachineStateChangeEvent::class);
        $event->method('getCustomerId')->willThrowException(new CustomerDeletedException('id'));

        $stored = [];
        $stored = $this->storer->store($event, $stored);
        static::assertArrayNotHasKey(CustomerAware::CUSTOMER_ID, $stored);
    }

    public function testStoreWithNotAware(): void
    {
        $event = $this->createMock(UserRecoveryRequestEvent::class);
        $stored = [];
        $stored = $this->storer->store($event, $stored);
        static::assertArrayNotHasKey(CustomerAware::CUSTOMER_ID, $stored);
    }

    public function testRestoreHasStored(): void
    {
        $storable = new StorableFlow('name', Context::createDefaultContext(), ['customerId' => 'test_id']);

        $this->storer->restore($storable);

        static::assertArrayHasKey('customer', $storable->data());
    }

    public function testRestoreEmptyStored(): void
    {
        $storable = new StorableFlow('name', Context::createDefaultContext());

        $this->storer->restore($storable);

        static::assertEmpty($storable->data());
    }

    public function testLazyLoadEntity(): void
    {
        $storable = new StorableFlow('name', Context::createDefaultContext(), ['customerId' => 'id'], []);
        $this->storer->restore($storable);
        $entity = new CustomerEntity();
        $result = $this->createMock(EntitySearchResult::class);
        $result->expects(static::once())->method('get')->willReturn($entity);

        $this->repository->expects(static::once())->method('search')->willReturn($result);
        $res = $storable->getData('customer');

        static::assertEquals($res, $entity);
    }

    public function testLazyLoadNullEntity(): void
    {
        $storable = new StorableFlow('name', Context::createDefaultContext(), ['customerId' => 'id'], []);
        $this->storer->restore($storable);
        $entity = null;
        $result = $this->createMock(EntitySearchResult::class);
        $result->expects(static::once())->method('get')->willReturn($entity);

        $this->repository->expects(static::once())->method('search')->willReturn($result);
        $res = $storable->getData('customer');

        static::assertEquals($res, $entity);
    }

    public function testLazyLoadNullId(): void
    {
        $storable = new StorableFlow('name', Context::createDefaultContext(), ['customerId' => null], []);
        $this->storer->restore($storable);
        $customerGroup = $storable->getData('customer');

        static::assertNull($customerGroup);
    }

    public function testDispatchBeforeLoadStorableFlowDataEvent(): void
    {
        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(
                static::isInstanceOf(BeforeLoadStorableFlowDataEvent::class),
                'flow.storer.customer.criteria.event'
            );

        $storable = new StorableFlow('name', Context::createDefaultContext(), ['customerId' => 'id'], []);
        $this->storer->restore($storable);
        $storable->getData('customer');
    }
}
