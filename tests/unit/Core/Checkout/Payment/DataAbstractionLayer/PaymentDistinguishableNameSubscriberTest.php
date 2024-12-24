<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentDistinguishableNameSubscriber;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
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
#[CoversClass(PaymentDistinguishableNameSubscriber::class)]
class PaymentDistinguishableNameSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        static::assertSame(
            [
                'payment_method.loaded' => 'addDistinguishablePaymentName',
            ],
            PaymentDistinguishableNameSubscriber::getSubscribedEvents()
        );
    }

    public function testAddName(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setName('test');
        $paymentMethod->addTranslated('name', 'translatedTest');

        $event = new EntityLoadedEvent(
            new PaymentMethodDefinition(),
            [$paymentMethod],
            Context::createDefaultContext()
        );

        $subscriber = new PaymentDistinguishableNameSubscriber();
        $subscriber->addDistinguishablePaymentName($event);

        static::assertSame('test', $paymentMethod->getDistinguishableName());
        static::assertSame('translatedTest', $paymentMethod->getTranslation('distinguishableName'));
    }
}
