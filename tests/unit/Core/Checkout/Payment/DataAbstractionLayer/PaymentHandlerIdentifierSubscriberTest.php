<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PreparedPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\RecurringPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentHandlerIdentifierSubscriber;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\App\Aggregate\AppPaymentMethod\AppPaymentMethodEntity;
use Cicada\Core\Framework\App\Payment\Handler\AppPaymentHandler;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentHandlerIdentifierSubscriber::class)]
class PaymentHandlerIdentifierSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        static::assertSame(
            [
                'payment_method.loaded' => 'formatHandlerIdentifier',
                'payment_method.partial_loaded' => 'formatHandlerIdentifier',
            ],
            PaymentHandlerIdentifierSubscriber::getSubscribedEvents()
        );
    }

    public function testMultipleFormatHandlerIdentifier(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);

        $paymentMethods = [
            $this->getPaymentMethod(SynchronousPaymentHandlerInterface::class),
            $this->getPaymentMethod(AsynchronousPaymentHandlerInterface::class),
            $this->getPaymentMethod(RefundPaymentHandlerInterface::class),
            $this->getPaymentMethod(PreparedPaymentHandlerInterface::class),
            $this->getPaymentMethod(RecurringPaymentHandlerInterface::class),
        ];

        $event = new EntityLoadedEvent(
            new PaymentMethodDefinition(),
            $paymentMethods,
            Context::createDefaultContext()
        );

        $subscriber = new PaymentHandlerIdentifierSubscriber();
        $subscriber->formatHandlerIdentifier($event);

        /** @var array<PaymentMethodEntity> $methods */
        $methods = $event->getEntities();

        static::assertContainsOnly(PaymentMethodEntity::class, $methods);
        static::assertCount(5, $methods);

        static::assertSame('handler_cicada_synchronouspaymenthandlerinterface', $methods[0]->getFormattedHandlerIdentifier());
        static::assertSame('handler_cicada_asynchronouspaymenthandlerinterface', $methods[1]->getFormattedHandlerIdentifier());
        static::assertSame('handler_cicada_refundpaymenthandlerinterface', $methods[2]->getFormattedHandlerIdentifier());
        static::assertSame('handler_cicada_preparedpaymenthandlerinterface', $methods[3]->getFormattedHandlerIdentifier());
        static::assertSame('handler_cicada_recurringpaymenthandlerinterface', $methods[4]->getFormattedHandlerIdentifier());

        if (Feature::isActive('v6.7.0.0')) {
            return;
        }

        static::assertTrue($methods[0]->isSynchronous());
        static::assertFalse($methods[0]->isAsynchronous());
        static::assertFalse($methods[0]->isRefundable());
        static::assertFalse($methods[0]->isPrepared());
        static::assertFalse($methods[0]->isRecurring());

        static::assertFalse($methods[1]->isSynchronous());
        static::assertTrue($methods[1]->isAsynchronous());
        static::assertFalse($methods[1]->isRefundable());
        static::assertFalse($methods[1]->isPrepared());
        static::assertFalse($methods[1]->isRecurring());

        static::assertFalse($methods[2]->isSynchronous());
        static::assertFalse($methods[2]->isAsynchronous());
        static::assertTrue($methods[2]->isRefundable());
        static::assertFalse($methods[2]->isPrepared());
        static::assertFalse($methods[2]->isRecurring());

        static::assertFalse($methods[3]->isSynchronous());
        static::assertFalse($methods[3]->isAsynchronous());
        static::assertFalse($methods[3]->isRefundable());
        static::assertTrue($methods[3]->isPrepared());
        static::assertFalse($methods[3]->isRecurring());

        static::assertFalse($methods[4]->isSynchronous());
        static::assertFalse($methods[4]->isAsynchronous());
        static::assertFalse($methods[4]->isRefundable());
        static::assertFalse($methods[4]->isPrepared());
        static::assertTrue($methods[4]->isRecurring());
    }

    public function testFormatHandlerIdentifier(): void
    {
        $paymentMethods = [
            $this->getPaymentMethod(AppPaymentHandler::class),
        ];

        $event = new EntityLoadedEvent(
            new PaymentMethodDefinition(),
            $paymentMethods,
            Context::createDefaultContext()
        );

        $subscriber = new PaymentHandlerIdentifierSubscriber();
        $subscriber->formatHandlerIdentifier($event);

        /** @var array<PaymentMethodEntity> $methods */
        $methods = $event->getEntities();

        static::assertContainsOnly(PaymentMethodEntity::class, $methods);
        static::assertCount(1, $methods);

        static::assertSame('handler_cicada_apppaymenthandler', $methods[0]->getFormattedHandlerIdentifier());
    }

    public function testNonNamespacedIdentifier(): void
    {
        $paymentMethods = [
            $this->getPaymentMethod('foo'),
        ];

        $event = new EntityLoadedEvent(
            new PaymentMethodDefinition(),
            $paymentMethods,
            Context::createDefaultContext()
        );

        $subscriber = new PaymentHandlerIdentifierSubscriber();
        $subscriber->formatHandlerIdentifier($event);

        /** @var array<PaymentMethodEntity> $methods */
        $methods = $event->getEntities();

        static::assertContainsOnly(PaymentMethodEntity::class, $methods);
        static::assertCount(1, $methods);

        static::assertSame('foo', $methods[0]->getFormattedHandlerIdentifier());

        if (Feature::isActive('v6.7.0.0')) {
            return;
        }

        static::assertFalse($methods[0]->isSynchronous());
        static::assertFalse($methods[0]->isAsynchronous());
        static::assertFalse($methods[0]->isRefundable());
        static::assertFalse($methods[0]->isPrepared());
        static::assertFalse($methods[0]->isRecurring());
    }

    public function testAppPaymentMethod(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);

        $method1 = $this->getPaymentMethod(SynchronousPaymentHandlerInterface::class);
        $method1->setAppPaymentMethod((new AppPaymentMethodEntity())->assign(['payUrl' => 'foo']));

        $method2 = $this->getPaymentMethod(AsynchronousPaymentHandlerInterface::class);
        $method2->setAppPaymentMethod((new AppPaymentMethodEntity())->assign(['payUrl' => 'foo', 'finalizeUrl' => 'bar']));

        $method3 = $this->getPaymentMethod(RefundPaymentHandlerInterface::class);
        $method3->setAppPaymentMethod((new AppPaymentMethodEntity())->assign(['refundUrl' => 'foo']));

        $method4 = $this->getPaymentMethod(PreparedPaymentHandlerInterface::class);
        $method4->setAppPaymentMethod((new AppPaymentMethodEntity())->assign(['validateUrl' => 'foo', 'captureUrl' => 'bar']));

        $method5 = $this->getPaymentMethod(RecurringPaymentHandlerInterface::class);
        $method5->setAppPaymentMethod((new AppPaymentMethodEntity())->assign(['recurringUrl' => 'foo']));

        $paymentMethods = [$method1, $method2, $method3, $method4, $method5];

        $event = new EntityLoadedEvent(
            new PaymentMethodDefinition(),
            $paymentMethods,
            Context::createDefaultContext()
        );

        $subscriber = new PaymentHandlerIdentifierSubscriber();
        $subscriber->formatHandlerIdentifier($event);

        /** @var array<PaymentMethodEntity> $methods */
        $methods = $event->getEntities();

        static::assertContainsOnly(PaymentMethodEntity::class, $methods);
        static::assertCount(5, $methods);

        static::assertSame('handler_cicada_synchronouspaymenthandlerinterface', $methods[0]->getFormattedHandlerIdentifier());
        static::assertSame('handler_cicada_asynchronouspaymenthandlerinterface', $methods[1]->getFormattedHandlerIdentifier());
        static::assertSame('handler_cicada_refundpaymenthandlerinterface', $methods[2]->getFormattedHandlerIdentifier());
        static::assertSame('handler_cicada_preparedpaymenthandlerinterface', $methods[3]->getFormattedHandlerIdentifier());
        static::assertSame('handler_cicada_recurringpaymenthandlerinterface', $methods[4]->getFormattedHandlerIdentifier());

        static::assertTrue($methods[0]->isSynchronous());
        static::assertFalse($methods[0]->isAsynchronous());
        static::assertFalse($methods[0]->isRefundable());
        static::assertFalse($methods[0]->isPrepared());
        static::assertFalse($methods[0]->isRecurring());

        static::assertFalse($methods[1]->isSynchronous());
        static::assertTrue($methods[1]->isAsynchronous());
        static::assertFalse($methods[1]->isRefundable());
        static::assertFalse($methods[1]->isPrepared());
        static::assertFalse($methods[1]->isRecurring());

        static::assertTrue($methods[2]->isSynchronous());
        static::assertFalse($methods[2]->isAsynchronous());
        static::assertTrue($methods[2]->isRefundable());
        static::assertFalse($methods[2]->isPrepared());
        static::assertFalse($methods[2]->isRecurring());

        static::assertTrue($methods[3]->isSynchronous());
        static::assertFalse($methods[3]->isAsynchronous());
        static::assertFalse($methods[3]->isRefundable());
        static::assertTrue($methods[3]->isPrepared());
        static::assertFalse($methods[3]->isRecurring());

        static::assertTrue($methods[4]->isSynchronous());
        static::assertFalse($methods[4]->isAsynchronous());
        static::assertFalse($methods[4]->isRefundable());
        static::assertFalse($methods[4]->isPrepared());
        static::assertTrue($methods[4]->isRecurring());
    }

    private function getPaymentMethod(string $identifierClass): PaymentMethodEntity
    {
        $entity = new PaymentMethodEntity();
        $entity->assign([
            'id' => Uuid::randomHex(),
            'handlerIdentifier' => $identifierClass,
        ]);

        return $entity;
    }
}
