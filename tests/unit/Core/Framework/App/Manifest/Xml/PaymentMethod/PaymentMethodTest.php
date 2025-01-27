<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Manifest\Xml\PaymentMethod;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\Manifest\Xml\PaymentMethod\PaymentMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PaymentMethod::class)]
class PaymentMethodTest extends TestCase
{
    public function testFromXml(): void
    {
        $manifest = Manifest::createFromXmlFile(__DIR__ . '/../../_fixtures/test/manifest.xml');

        static::assertNotNull($manifest->getPayments());
        $paymentMethods = $manifest->getPayments()->getPaymentMethods();
        static::assertCount(2, $paymentMethods);

        $firstPaymentMethod = $paymentMethods[0];
        static::assertSame('myMethod', $firstPaymentMethod->getIdentifier());
        static::assertSame('https://payment.app/payment/process', $firstPaymentMethod->getPayUrl());
        static::assertSame('https://payment.app/payment/finalize', $firstPaymentMethod->getFinalizeUrl());
        static::assertSame('https://payment.app/payment/refund', $firstPaymentMethod->getRefundUrl());
        static::assertSame('https://payment.app/payment/recurring', $firstPaymentMethod->getRecurringUrl());
        static::assertSame('Resources/payment.png', $firstPaymentMethod->getIcon());
        static::assertSame([
            'zh-CN' => 'The app payment method',
            'en-GB' => 'Die App Zahlungsmethode',
        ], $firstPaymentMethod->getName());
        static::assertSame([
            'zh-CN' => 'This is a description',
            'en-GB' => 'Die Zahlungsmethoden-Beschreibung',
        ], $firstPaymentMethod->getDescription());

        $secondPaymentMethod = $paymentMethods[1];
        static::assertSame('anotherMethod', $secondPaymentMethod->getIdentifier());
        static::assertNull($secondPaymentMethod->getPayUrl());
        static::assertNull($secondPaymentMethod->getFinalizeUrl());
        static::assertNull($secondPaymentMethod->getRefundUrl());
        static::assertNull($secondPaymentMethod->getRecurringUrl());
        static::assertNull($secondPaymentMethod->getIcon());
        static::assertSame([
            'zh-CN' => 'Another app payment method',
        ], $secondPaymentMethod->getName());
        static::assertSame([
            'zh-CN' => 'This is another description',
        ], $secondPaymentMethod->getDescription());
    }
}
