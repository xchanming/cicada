<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Storer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Cicada\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\MailStorer;
use Cicada\Core\Content\Test\Flow\TestFlowBusinessEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Flow\DummyEvent;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(MailStorer::class)]
class MailStorerTest extends TestCase
{
    private MailStorer $storer;

    protected function setUp(): void
    {
        $this->storer = new MailStorer();
    }

    public function testStoreWithAware(): void
    {
        $event = $this->createMock(OrderStateMachineStateChangeEvent::class);
        $stored = [];
        $stored = $this->storer->store($event, $stored);
        static::assertArrayHasKey(MailAware::MAIL_STRUCT, $stored);
        static::assertArrayHasKey(MailAware::SALES_CHANNEL_ID, $stored);
    }

    public function testStoreWithNotAware(): void
    {
        $event = $this->createMock(TestFlowBusinessEvent::class);
        $stored = [];
        $stored = $this->storer->store($event, $stored);
        static::assertArrayNotHasKey(MailAware::MAIL_STRUCT, $stored);
        static::assertArrayNotHasKey(MailAware::SALES_CHANNEL_ID, $stored);
    }

    public function testRestoreHasStored(): void
    {
        $store = [
            'recipients' => ['firstName' => 'test'],
            'bcc' => 'bcc',
            'cc' => 'cc',
        ];

        $flow = new StorableFlow('test', Context::createDefaultContext(), [MailAware::MAIL_STRUCT => $store]);

        $this->storer->restore($flow);

        static::assertTrue($flow->hasData(MailAware::MAIL_STRUCT));

        static::assertInstanceOf(MailRecipientStruct::class, $flow->getData(MailAware::MAIL_STRUCT));

        static::assertEquals('test', $flow->getData(MailAware::MAIL_STRUCT)->getRecipients()['firstName']);
        static::assertEquals('bcc', $flow->getData(MailAware::MAIL_STRUCT)->getBcc());
        static::assertEquals('cc', $flow->getData(MailAware::MAIL_STRUCT)->getCc());
    }

    public function testRestoreHasDataOrder(): void
    {
        $flow = new StorableFlow('test', Context::createDefaultContext(), [OrderAware::ORDER_ID => Uuid::randomHex()]);
        $customer = new OrderCustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setFirstName('bar');
        $customer->setLastName('foo');
        $customer->setEmail('foo@bar.com');
        $order = new OrderEntity();
        $order->setOrderCustomer($customer);
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $flow->setData(OrderAware::ORDER, $order);

        $this->storer->restore($flow);

        static::assertTrue($flow->hasData(MailAware::MAIL_STRUCT));

        static::assertInstanceOf(MailRecipientStruct::class, $flow->getData(MailAware::MAIL_STRUCT));
        static::assertEquals('barfoo', $flow->getData(MailAware::MAIL_STRUCT)->getRecipients()['foo@bar.com']);
        static::assertNull($flow->getData(MailAware::MAIL_STRUCT)->getBcc());
        static::assertNull($flow->getData(MailAware::MAIL_STRUCT)->getCc());
    }

    public function testRestoreHasDataCustomer(): void
    {
        $flow = new StorableFlow('test', Context::createDefaultContext(), [OrderAware::ORDER_ID => Uuid::randomHex()]);
        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setFirstName('bar');
        $customer->setLastName('foo');
        $customer->setEmail('foo@bar.com');
        $customer->setSalesChannelId(TestDefaults::SALES_CHANNEL);

        $flow->setData(CustomerAware::CUSTOMER, $customer);

        $this->storer->restore($flow);

        static::assertTrue($flow->hasData(MailAware::MAIL_STRUCT));

        static::assertInstanceOf(MailRecipientStruct::class, $flow->getData(MailAware::MAIL_STRUCT));
        static::assertEquals('barfoo', $flow->getData(MailAware::MAIL_STRUCT)->getRecipients()['foo@bar.com']);
        static::assertNull($flow->getData(MailAware::MAIL_STRUCT)->getBcc());
        static::assertNull($flow->getData(MailAware::MAIL_STRUCT)->getCc());
    }
}

/**
 * @internal
 */
class MailEvent extends DummyEvent implements MailAware
{
    public function __construct(private readonly MailRecipientStruct $recipients)
    {
    }

    public function getMailStruct(): MailRecipientStruct
    {
        return $this->recipients;
    }

    public function getSalesChannelId(): ?string
    {
        return TestDefaults::SALES_CHANNEL;
    }
}
