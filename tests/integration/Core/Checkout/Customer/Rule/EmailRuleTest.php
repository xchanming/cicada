<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\Rule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Rule\EmailRule;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('services-settings')]
class EmailRuleTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private EntityRepository $ruleRepository;

    private EntityRepository $conditionRepository;

    private Context $context;

    private EmailRule $rule;

    protected function setUp(): void
    {
        $this->ruleRepository = static::getContainer()->get('rule.repository');
        $this->conditionRepository = static::getContainer()->get('rule_condition.repository');
        $this->context = Context::createDefaultContext();
        $this->rule = new EmailRule();
    }

    public function testValidateWithMissingEmail(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new EmailRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(2, $exceptions);
            static::assertSame('/0/value/email', $exceptions[1]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[0]['code']);

            static::assertSame('/0/value/operator', $exceptions[0]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[1]['code']);
        }
    }

    public function testValidateWithEmptyEmail(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new EmailRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                    'value' => [
                        'email' => '',
                        'operator' => Rule::OPERATOR_EQ,
                    ],
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(1, $exceptions);
            static::assertSame('/0/value/email', $exceptions[0]['source']['pointer']);
            static::assertSame(NotBlank::IS_BLANK_ERROR, $exceptions[0]['code']);
        }
    }

    public function testValidateWithInvalidEmail(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new EmailRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                    'value' => [
                        'email' => true,
                        'operator' => Rule::OPERATOR_EQ,
                    ],
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(1, $exceptions);
            static::assertSame('/0/value/email', $exceptions[0]['source']['pointer']);
            static::assertSame(Type::INVALID_TYPE_ERROR, $exceptions[0]['code']);
        }
    }

    public function testIfRuleIsConsistent(): void
    {
        $ruleId = Uuid::randomHex();
        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            $this->context
        );

        $id = Uuid::randomHex();
        $this->conditionRepository->create([
            [
                'id' => $id,
                'type' => (new EmailRule())->getName(),
                'ruleId' => $ruleId,
                'value' => [
                    'email' => 'Type',
                    'operator' => Rule::OPERATOR_EQ,
                ],
            ],
        ], $this->context);

        static::assertNotNull($this->conditionRepository->search(new Criteria([$id]), $this->context)->get($id));
        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $id]], $this->context);
    }

    public function testConstraints(): void
    {
        $expectedOperators = [
            Rule::OPERATOR_EQ,
            Rule::OPERATOR_NEQ,
        ];

        $ruleConstraints = $this->rule->getConstraints();

        static::assertArrayHasKey('operator', $ruleConstraints, 'Constraint operator not found in Rule');
        $operators = $ruleConstraints['operator'];
        static::assertEquals(new NotBlank(), $operators[0]);
        static::assertEquals(new Choice($expectedOperators), $operators[1]);

        $this->rule->assign(['operator' => Rule::OPERATOR_EQ]);
        static::assertArrayHasKey('email', $ruleConstraints, 'Constraint email not found in Rule');
        $email = $ruleConstraints['email'];
        static::assertEquals(new NotBlank(), $email[0]);
        static::assertEquals(new Type('string'), $email[1]);
    }

    #[DataProvider('getMatchValues')]
    public function testRuleMatching(string $operator, string $customerEmail, string $email, bool $expected, bool $noCustomer = false): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $customer = new CustomerEntity();
        $customer->setEmail($customerEmail);

        if ($noCustomer) {
            $customer = null;
        }

        $salesChannelContext->method('getCustomer')->willReturn($customer);
        $scope = new CheckoutRuleScope($salesChannelContext);
        $this->rule->assign(['email' => $email, 'operator' => $operator]);

        $match = $this->rule->match($scope);

        static::assertSame($expected, $match);
    }

    /**
     * @return \Traversable<string, array<string|bool>>
     */
    public static function getMatchValues(): \Traversable
    {
        // OPERATOR_EQ
        yield 'operator_eq / match exact / email' => [Rule::OPERATOR_EQ, 'test@example.com', 'test@example.com', true];
        yield 'operator_eq / not match exact / email' => [Rule::OPERATOR_EQ, 'test@example.com', 'foo@example.com', false];
        yield 'operator_eq / match partially between / email' => [Rule::OPERATOR_EQ, 'test@example.com', 'te*@exa*le.com', true];
        yield 'operator_eq / match partially start / email' => [Rule::OPERATOR_EQ, 'test@example.com', '*@example.com', true];
        yield 'operator_eq / match partially end / email' => [Rule::OPERATOR_EQ, 'test@example.com', 'test@*', true];
        yield 'operator_eq / not match partially between / email' => [Rule::OPERATOR_EQ, 'test@example.com', 'foo@*.com', false];
        yield 'operator_eq / not match partially start / email' => [Rule::OPERATOR_EQ, 'test@example.com', '*@cicada.com', false];
        yield 'operator_eq / not match partially end / email' => [Rule::OPERATOR_EQ, 'test@example.com', 'foo@*', false];
        yield 'operator_eq / no match / no customer' => [Rule::OPERATOR_EQ, 'test@example.com', 'test@example.com', false, true];

        // OPERATOR_NEQ
        yield 'operator_neq / not match exact / email' => [Rule::OPERATOR_NEQ, 'test@example.com', 'foo@example.com', true];
        yield 'operator_neq / match exact / email' => [Rule::OPERATOR_NEQ, 'test@example.com', 'test@example.com', false];
        yield 'operator_neq / match partially between / email' => [Rule::OPERATOR_NEQ, 'test@example.com', 'te*@exa*le.com', false];
        yield 'operator_neq / match partially start / email' => [Rule::OPERATOR_NEQ, 'test@example.com', '*@example.com', false];
        yield 'operator_neq / match partially end / email' => [Rule::OPERATOR_NEQ, 'test@example.com', 'test@*', false];
        yield 'operator_neq / not match partially between / email' => [Rule::OPERATOR_NEQ, 'test@example.com', 'foo@*.com', true];
        yield 'operator_neq / not match partially start / email' => [Rule::OPERATOR_NEQ, 'test@example.com', '*@cicada.com', true];
        yield 'operator_neq / not match partially end / email' => [Rule::OPERATOR_NEQ, 'test@example.com', 'foo@*', true];

        yield 'operator_neq / match / no customer' => [Rule::OPERATOR_NEQ, 'test@example.com', 'test@example.com', true, true];
    }
}
