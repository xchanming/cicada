<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Rule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\ShippingMethodRule;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\Constraint\ArrayOfUuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('services-settings')]
class ShippingMethodRuleTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private EntityRepository $ruleRepository;

    private EntityRepository $conditionRepository;

    private Context $context;

    protected function setUp(): void
    {
        $this->ruleRepository = static::getContainer()->get('rule.repository');
        $this->conditionRepository = static::getContainer()->get('rule_condition.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testValidateWithMissingShippingMethodIds(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new ShippingMethodRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(2, $exceptions);
            static::assertSame('/0/value/shippingMethodIds', $exceptions[0]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[0]['code']);

            static::assertSame('/0/value/operator', $exceptions[1]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[1]['code']);
        }
    }

    public function testValidateWithEmptyShippingMethodIds(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new ShippingMethodRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                    'value' => [
                        'shippingMethodIds' => [],
                        'operator' => Rule::OPERATOR_EQ,
                    ],
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(1, $exceptions);
            static::assertSame('/0/value/shippingMethodIds', $exceptions[0]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[0]['code']);
        }
    }

    public function testValidateWithStringShippingMethodIds(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new ShippingMethodRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                    'value' => [
                        'shippingMethodIds' => '0915d54fbf80423c917c61ad5a391b48',
                        'operator' => Rule::OPERATOR_EQ,
                    ],
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(1, $exceptions);
            static::assertSame('/0/value/shippingMethodIds', $exceptions[0]['source']['pointer']);
            static::assertSame(Type::INVALID_TYPE_ERROR, $exceptions[0]['code']);
        }
    }

    public function testValidateWithInvalidShippingMethodIdsUuid(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new ShippingMethodRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                    'value' => [
                        'shippingMethodIds' => [true, 3, null, '0915d54fbf80423c917c61ad5a391b48'],
                        'operator' => Rule::OPERATOR_EQ,
                    ],
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(3, $exceptions);
            static::assertSame('/0/value/shippingMethodIds', $exceptions[0]['source']['pointer']);
            static::assertSame('/0/value/shippingMethodIds', $exceptions[1]['source']['pointer']);
            static::assertSame('/0/value/shippingMethodIds', $exceptions[2]['source']['pointer']);

            static::assertSame(ArrayOfUuid::INVALID_TYPE_CODE, $exceptions[0]['code']);
            static::assertSame(ArrayOfUuid::INVALID_TYPE_CODE, $exceptions[1]['code']);
            static::assertSame(ArrayOfUuid::INVALID_TYPE_CODE, $exceptions[2]['code']);
        }
    }

    public function testAvailableOperators(): void
    {
        $ruleId = Uuid::randomHex();
        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            Context::createDefaultContext()
        );

        $conditionIdEq = Uuid::randomHex();
        $conditionIdNEq = Uuid::randomHex();
        $this->conditionRepository->create(
            [
                [
                    'id' => $conditionIdEq,
                    'type' => (new ShippingMethodRule())->getName(),
                    'ruleId' => $ruleId,
                    'value' => [
                        'operator' => Rule::OPERATOR_EQ,
                        'shippingMethodIds' => [Uuid::randomHex()],
                    ],
                ],
                [
                    'id' => $conditionIdNEq,
                    'type' => (new ShippingMethodRule())->getName(),
                    'ruleId' => $ruleId,
                    'value' => [
                        'operator' => Rule::OPERATOR_EQ,
                        'shippingMethodIds' => [Uuid::randomHex()],
                    ],
                ],
            ],
            $this->context
        );

        static::assertCount(
            2,
            $this->conditionRepository->search(
                new Criteria([$conditionIdEq, $conditionIdNEq]),
                $this->context
            )
        );
    }

    public function testValidateWithInvalidOperators(): void
    {
        foreach ([Rule::OPERATOR_LTE, Rule::OPERATOR_GTE, 'Invalid', true, 1.1] as $operator) {
            try {
                $this->conditionRepository->create([
                    [
                        'type' => (new ShippingMethodRule())->getName(),
                        'ruleId' => Uuid::randomHex(),
                        'value' => [
                            'operator' => $operator,
                            'shippingMethodIds' => [Uuid::randomHex(), Uuid::randomHex()],
                        ],
                    ],
                ], $this->context);
                static::fail('Exception was not thrown');
            } catch (WriteException $stackException) {
                $exceptions = iterator_to_array($stackException->getErrors());
                static::assertCount(1, $exceptions);
                static::assertSame('/0/value/operator', $exceptions[0]['source']['pointer']);
                static::assertSame(Choice::NO_SUCH_CHOICE_ERROR, $exceptions[0]['code']);
            }
        }
    }

    public function testIfRuleIsConsistent(): void
    {
        $ruleId = Uuid::randomHex();
        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            Context::createDefaultContext()
        );

        $id = Uuid::randomHex();
        $this->conditionRepository->create([
            [
                'id' => $id,
                'type' => (new ShippingMethodRule())->getName(),
                'ruleId' => $ruleId,
                'value' => [
                    'operator' => Rule::OPERATOR_EQ,
                    'shippingMethodIds' => [Uuid::randomHex(), Uuid::randomHex()],
                ],
            ],
        ], $this->context);

        static::assertNotNull($this->conditionRepository->search(new Criteria([$id]), $this->context)->get($id));
    }

    /**
     * @return array<array<string|bool|array<string, string|array<string>>>>
     */
    public static function matchDataProvider(): array
    {
        return [
            [
                [
                    'operator' => Rule::OPERATOR_EQ,
                    'shippingMethodIds' => [],
                ],
                '965a0713093841ceb86b0f83edd7dab4',
                false,
            ],
            [
                [
                    'operator' => Rule::OPERATOR_EQ,
                    'shippingMethodIds' => ['ff5a0713093841ceb86b0f83edd7dab4'],
                ],
                '965a0713093841ceb86b0f83edd7dab4',
                false,
            ],
            [
                [
                    'operator' => Rule::OPERATOR_NEQ,
                    'shippingMethodIds' => ['965a0713093841ceb86b0f83edd7dab4'],
                ],
                '965a0713093841ceb86b0f83edd7dab4',
                false,
            ],
            [
                [
                    'operator' => Rule::OPERATOR_NEQ,
                    'shippingMethodIds' => ['965a0713093841ceb86b0f83edd7dab4', 'ff5a0713093841ceb86b0f83edd7dab4'],
                ],
                'ff5a0713093841ceb86b0f83edd7dab4',
                false,
            ],
            [
                [
                    'operator' => Rule::OPERATOR_EQ,
                    'shippingMethodIds' => ['965a0713093841ceb86b0f83edd7dab4'],
                ],
                '965a0713093841ceb86b0f83edd7dab4',
                true,
            ],
            [
                [
                    'operator' => Rule::OPERATOR_EQ,
                    'shippingMethodIds' => ['965a0713093841ceb86b0f83edd7dab4', 'ff5a0713093841ceb86b0f83edd7dab4'],
                ],
                'ff5a0713093841ceb86b0f83edd7dab4',
                true,
            ],
            [
                [
                    'operator' => Rule::OPERATOR_NEQ,
                    'shippingMethodIds' => ['965a0713093841ceb86b0f83edd7dab4', 'ff5a0713093841ceb86b0f83edd7dab4'],
                ],
                'ee5a0713093841ceb86b0f83edd7dab4',
                true,
            ],
        ];
    }

    /**
     * @param array<string, string|array<string>> $ruleProperties
     */
    #[DataProvider('matchDataProvider')]
    public function testMatch(array $ruleProperties, string $shippingMethodId, bool $expected): void
    {
        $shippingRule = new ShippingMethodRule();
        $shippingRule->assign($ruleProperties);

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId($shippingMethodId);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getShippingMethod')->willReturn($shippingMethod);

        $ruleScope = new CartRuleScope(
            new Cart('test'),
            $salesChannelContext
        );

        static::assertSame($expected, $shippingRule->match($ruleScope));
    }

    public function testExpectUnsupportedOperatorException(): void
    {
        $shippingMethodRule = new ShippingMethodRule();
        $shippingMethodRule->assign(['operator' => 'FOO', 'shippingMethodIds' => []]);

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId('965a0713093841ceb86b0f83edd7dab4');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getShippingMethod')->willReturn($shippingMethod);

        $ruleScope = new CartRuleScope(
            new Cart('test'),
            $salesChannelContext
        );

        $this->expectException(UnsupportedOperatorException::class);
        $this->expectExceptionMessage('Unsupported operator FOO in Cicada\Core\Framework\Rule\RuleComparison');

        $shippingMethodRule->match($ruleScope);
    }
}
