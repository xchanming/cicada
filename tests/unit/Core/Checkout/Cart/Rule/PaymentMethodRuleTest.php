<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Rule\PaymentMethodRule;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\Framework\Validation\Constraint\ArrayOfUuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(PaymentMethodRule::class)]
#[Group('rules')]
class PaymentMethodRuleTest extends TestCase
{
    public function testNameReturnsKnownName(): void
    {
        $rule = new PaymentMethodRule();

        static::assertSame('paymentMethod', $rule->getName());
    }

    public function testGetApiAlias(): void
    {
        $rule = new PaymentMethodRule();

        static::assertSame('rule_paymentMethod', $rule->getApiAlias());
    }

    public function testJsonSerializeAddsName(): void
    {
        $rule = new PaymentMethodRule();

        $json = $rule->jsonSerialize();

        static::assertSame('paymentMethod', $json['_name']);
    }

    public function testGetConstraintsOfRule(): void
    {
        $rule = new PaymentMethodRule();

        $constraints = $rule->getConstraints();
        static::assertCount(2, $constraints['paymentMethodIds']);
        static::assertInstanceOf(NotBlank::class, $constraints['paymentMethodIds'][0]);
        static::assertInstanceOf(ArrayOfUuid::class, $constraints['paymentMethodIds'][1]);
        static::assertIsArray($constraints['operator']);
        static::assertCount(2, $constraints['operator']);
        static::assertInstanceOf(NotBlank::class, $constraints['operator'][0]);
        static::assertInstanceOf(Choice::class, $constraints['operator'][1]);
    }

    public function testRuleDoesNotMatchNoPaymentIds(): void
    {
        $rule = new PaymentMethodRule();
        $paymentMethodeEntity = new PaymentMethodEntity();
        $paymentMethodeEntity->setId('foo');

        $salesChannelContextMock = $this->getMockBuilder(SalesChannelContext::class)->disableOriginalConstructor()->getMock();
        $salesChannelContextMock->method('getPaymentMethod')->willReturn($paymentMethodeEntity);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getSalesChannelContext')->willReturn($salesChannelContextMock);

        static::assertFalse($rule->match($ruleScope));
    }

    public function testRuleMatchesPaymentId(): void
    {
        $rule = new PaymentMethodRule(Rule::OPERATOR_EQ, ['foo']);
        $paymentMethodeEntity = new PaymentMethodEntity();
        $paymentMethodeEntity->setId('foo');

        $salesChannelContextMock = $this->getMockBuilder(SalesChannelContext::class)->disableOriginalConstructor()->getMock();
        $salesChannelContextMock->method('getPaymentMethod')->willReturn($paymentMethodeEntity);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getSalesChannelContext')->willReturn($salesChannelContextMock);

        static::assertTrue($rule->match($ruleScope));
    }

    public function testGetDefaultConfig(): void
    {
        $rule = new PaymentMethodRule();

        $config = $rule->getConfig()->getData();
        static::assertSame([
            'operatorSet' => [
                'operators' => [
                    Rule::OPERATOR_EQ,
                    Rule::OPERATOR_NEQ,
                ],
                'isMatchAny' => true,
            ],
            'fields' => [
                'paymentMethodIds' => [
                    'name' => 'paymentMethodIds',
                    'type' => 'multi-entity-id-select',
                    'config' => [
                        'entity' => 'payment_method',
                    ],
                ],
            ],
        ], $config);
    }
}
