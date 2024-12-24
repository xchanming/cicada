<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Order\Transformer;

use Cicada\Core\Checkout\Cart\Order\Transformer\TransactionTransformer;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Cart\Transaction\Struct\Transaction;
use Cicada\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TransactionTransformer::class)]
class TransactionTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transaction = new Transaction(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()), 'test');
        $transaction->setValidationStruct(new ArrayStruct());

        $data = TransactionTransformer::transform($transaction, 'state', Context::createDefaultContext());

        static::assertSame('test', $data['paymentMethodId']);
        static::assertSame($transaction->getAmount(), $data['amount']);
        static::assertSame('state', $data['stateId']);
        static::assertSame($transaction->getValidationStruct()?->jsonSerialize(), $data['validationData']);
    }

    public function testTransformCollection(): void
    {
        $transaction = new Transaction(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()), 'test');
        $transaction->setValidationStruct(new ArrayStruct());

        $data = TransactionTransformer::transformCollection(new TransactionCollection([$transaction]), 'state', Context::createDefaultContext());
        $data = $data[0];

        static::assertSame('test', $data['paymentMethodId']);
        static::assertSame($transaction->getAmount(), $data['amount']);
        static::assertSame('state', $data['stateId']);
        static::assertSame($transaction->getValidationStruct()?->jsonSerialize(), $data['validationData']);
    }
}
