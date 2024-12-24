<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Handler;

use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PreparedPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentHandlerIdentifierSubscriber;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Integration\PaymentHandler\MultipleTestPaymentHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - will be removed with new payment handlers
 */
#[Package('checkout')]
#[CoversClass(PaymentHandlerIdentifierSubscriber::class)]
class PaymentMethodHandlerRuntimeFieldTest extends TestCase
{
    public function testSynchronousRuntimeField(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);

        $event = $this->createMock(EntityLoadedEvent::class);
        $event
            ->method('getEntities')
            ->willReturn($this->getPaymentMethodEntity(
                $this->createMock(SynchronousPaymentHandlerInterface::class)::class
            ));

        (new PaymentHandlerIdentifierSubscriber())->formatHandlerIdentifier($event);

        static::assertCount(1, $event->getEntities());

        /** @var PaymentMethodEntity $paymentMethod */
        $paymentMethod = $event->getEntities()[0];

        static::assertTrue($paymentMethod->isSynchronous());
        static::assertFalse($paymentMethod->isAsynchronous());
        static::assertFalse($paymentMethod->isPrepared());
    }

    public function testAsynchronousRuntimeField(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);
        $event = $this->createMock(EntityLoadedEvent::class);
        $event
            ->method('getEntities')
            ->willReturn($this->getPaymentMethodEntity(
                $this->createMock(AsynchronousPaymentHandlerInterface::class)::class
            ));

        (new PaymentHandlerIdentifierSubscriber())->formatHandlerIdentifier($event);

        static::assertCount(1, $event->getEntities());

        /** @var PaymentMethodEntity $paymentMethod */
        $paymentMethod = $event->getEntities()[0];

        static::assertFalse($paymentMethod->isSynchronous());
        static::assertTrue($paymentMethod->isAsynchronous());
        static::assertFalse($paymentMethod->isPrepared());
    }

    public function testPreparedRuntimeField(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);
        $event = $this->createMock(EntityLoadedEvent::class);
        $event
            ->method('getEntities')
            ->willReturn($this->getPaymentMethodEntity(
                $this->createMock(PreparedPaymentHandlerInterface::class)::class
            ));

        (new PaymentHandlerIdentifierSubscriber())->formatHandlerIdentifier($event);

        static::assertCount(1, $event->getEntities());

        /** @var PaymentMethodEntity $paymentMethod */
        $paymentMethod = $event->getEntities()[0];

        static::assertFalse($paymentMethod->isSynchronous());
        static::assertFalse($paymentMethod->isAsynchronous());
        static::assertTrue($paymentMethod->isPrepared());
    }

    public function testMultipleRuntimeFieldsAtOnce(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);
        $event = $this->createMock(EntityLoadedEvent::class);
        $event
            ->method('getEntities')
            ->willReturn($this->getPaymentMethodEntity(
                $this->createMock(MultipleTestPaymentHandler::class)::class
            ));

        (new PaymentHandlerIdentifierSubscriber())->formatHandlerIdentifier($event);

        static::assertCount(1, $event->getEntities());

        /** @var PaymentMethodEntity $paymentMethod */
        $paymentMethod = $event->getEntities()[0];

        static::assertTrue($paymentMethod->isSynchronous());
        static::assertFalse($paymentMethod->isAsynchronous());
        static::assertTrue($paymentMethod->isPrepared());
    }

    /**
     * @return PaymentMethodEntity[]
     */
    private function getPaymentMethodEntity(string $handlerIdentifier): array
    {
        return [(new PaymentMethodEntity())->assign(['handlerIdentifier' => $handlerIdentifier])];
    }
}
