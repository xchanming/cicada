<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentHandlerIdentifierSubscriber;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\App\Payment\Handler\AppPaymentHandler;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
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

        static::assertContainsOnlyInstancesOf(PaymentMethodEntity::class, $methods);
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

        static::assertContainsOnlyInstancesOf(PaymentMethodEntity::class, $methods);
        static::assertCount(1, $methods);

        static::assertSame('foo', $methods[0]->getFormattedHandlerIdentifier());
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
