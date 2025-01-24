<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Rule;

use Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionCollection;
use Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionEntity;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class RuleValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Context $context;

    /**
     * @var EntityRepository<RuleConditionCollection>
     */
    private EntityRepository $ruleConditionRepository;

    /**
     * @var EntityRepository<RuleCollection>
     */
    private EntityRepository $ruleRepository;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->ruleRepository = static::getContainer()->get('rule.repository');
        $this->ruleConditionRepository = static::getContainer()->get('rule_condition.repository');
    }

    /**
     * @param array<array<string, string|array<string, mixed>>> $conditions
     */
    #[DataProvider('providerRuleCases')]
    public function testItCanCreateRulesOnValidInput(string $conditionId, array $conditions): void
    {
        $ruleId = Uuid::randomHex();

        $this->ruleRepository->create([
            [
                'id' => $ruleId,
                'name' => 'super rule',
                'priority' => 15,
                'conditions' => $conditions,
            ],
        ], $this->context);

        $criteria = new Criteria([$ruleId]);
        $criteria->addAssociation('conditions');

        /** @var RuleEntity $rule */
        $rule = $this->ruleRepository->search($criteria, $this->context)->getEntities()->get($ruleId);

        /** @var RuleConditionCollection $ruleConditions */
        $ruleConditions = $rule->getConditions();
        static::assertEquals(1, $ruleConditions->count());
        static::assertNotNull($ruleConditions->get($conditionId));
    }

    public function testItThrowsIfTypeIsMissing(): void
    {
        $ruleData = [
            [
                'name' => 'super rule',
                'priority' => 15,
                'conditions' => [
                    [
                        'type' => 'customerOrderCount',
                        'value' => [
                            'operator' => '=',
                            'count' => 6,
                        ],
                    ], [
                        'value' => [
                            'operator' => '=',
                            'count' => 6,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $this->ruleRepository->create($ruleData, $this->context);
        } catch (WriteException $we) {
            $violations = iterator_to_array($we->getErrors());

            static::assertCount(1, $violations);
            static::assertEquals('/0/conditions/1/type', $violations[0]['source']['pointer']);
        }
    }

    public function testWithChildren(): void
    {
        $ruleData = [
            [
                'name' => 'super rule',
                'priority' => 15,
                'conditions' => [
                    [
                        'type' => 'customerOrderCount',
                        'value' => [
                            'operator' => '=',
                            'count' => 6,
                        ],
                        'children' => [
                            [
                                'value' => [
                                    'operator' => '=',
                                    'count' => 6,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $this->ruleRepository->create($ruleData, $this->context);
        } catch (WriteException $we) {
            $violations = iterator_to_array($we->getErrors());

            static::assertCount(1, $violations);
            static::assertEquals('/0/conditions/0/children/0/type', $violations[0]['source']['pointer']);
        }
    }

    public function testItThrowsIfTypeIsInvalid(): void
    {
        $ruleData = [
            [
                'name' => 'super rule',
                'priority' => 15,
                'conditions' => [
                    [
                        'type' => 'someTypeThatIsHopefullyNotRegistered',
                        'value' => [
                            'operator' => '=',
                            'count' => 6,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $this->ruleRepository->create($ruleData, $this->context);
        } catch (WriteException $we) {
            $violations = iterator_to_array($we->getErrors());

            static::assertCount(1, $violations);
            static::assertEquals('/0/conditions/0/type', $violations[0]['source']['pointer']);
        }
    }

    public function testItThrowsIfValueIsMissing(): void
    {
        $ruleData = [
            [
                'name' => 'super rule',
                'priority' => 15,
                'conditions' => [
                    [
                        'type' => 'customerOrderCount',
                    ],
                ],
            ],
        ];

        try {
            $this->ruleRepository->create($ruleData, $this->context);
        } catch (WriteException $we) {
            $violations = iterator_to_array($we->getErrors());

            static::assertCount(2, $violations);
            static::assertEquals('/0/conditions/0/value/count', $violations[0]['source']['pointer']);
            static::assertEquals('/0/conditions/0/value/operator', $violations[1]['source']['pointer']);
        }
    }

    public function testItThrowsIfValueContainsInvalidField(): void
    {
        $ruleData = [
            [
                'name' => 'super rule',
                'priority' => 15,
                'conditions' => [
                    [
                        'type' => 'customerOrderCount',
                        'value' => [
                            'operator' => '=',
                            'count' => 6,
                            'thisFieldIsNotValid' => true,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $this->ruleRepository->create($ruleData, $this->context);
        } catch (WriteException $we) {
            $violations = iterator_to_array($we->getErrors());

            static::assertCount(1, $violations);
            static::assertEquals('/0/conditions/0/value/thisFieldIsNotValid', $violations[0]['source']['pointer']);
        }
    }

    public function testItCanUpdateValueOnly(): void
    {
        $customerOderCountId = Uuid::randomHex();

        $this->ruleRepository->create([
            [
                'name' => 'super rule',
                'priority' => 15,
                'conditions' => [
                    [
                        'id' => $customerOderCountId,
                        'type' => 'customerOrderCount',
                        'value' => [
                            'operator' => '=',
                            'count' => 6,
                        ],
                    ],
                ],
            ],
        ], $this->context);

        $newValue = [
            'operator' => '=',
            'count' => 12,
        ];

        $this->ruleConditionRepository->update([
            [
                'id' => $customerOderCountId,
                'value' => $newValue,
            ],
        ], $this->context);

        /** @var RuleConditionEntity $updatedCondition */
        $updatedCondition = $this->ruleConditionRepository->search(new Criteria([$customerOderCountId]), $this->context)
            ->getEntities()->get($customerOderCountId);

        static::assertEquals('customerOrderCount', $updatedCondition->getType());
        static::assertEquals($newValue, $updatedCondition->getValue());
    }

    public function testItThrowsIfNewTypeMismatchesValue(): void
    {
        $customerOrderCountId = Uuid::randomHex();

        $this->ruleRepository->create([
            [
                'name' => 'super rule',
                'priority' => 15,
                'conditions' => [
                    [
                        'id' => $customerOrderCountId,
                        'type' => 'customerOrderCount',
                        'value' => [
                            'operator' => '=',
                            'count' => 6,
                        ],
                    ],
                ],
            ],
        ], $this->context);

        try {
            $this->ruleConditionRepository->update([
                [
                    'id' => $customerOrderCountId,
                    'type' => 'orContainer',
                ],
            ], $this->context);
        } catch (WriteException $we) {
            $violations = iterator_to_array($we->getErrors());
            $pointer = array_column(array_column($violations, 'source'), 'pointer');

            static::assertCount(2, $pointer);
            static::assertContains('/0/value/count', $pointer);
            static::assertContains('/0/value/operator', $pointer);
        }
    }

    /**
     * @return iterable<string, array{string, array<array<string, string|array<string, mixed>>>}>
     */
    public static function providerRuleCases(): iterable
    {
        $conditionId = Uuid::randomHex();
        yield 'customerOrderCount rule' => [$conditionId, [
            [
                'id' => $conditionId,
                'type' => 'customerOrderCount',
                'value' => [
                    'operator' => '=',
                    'count' => 6,
                ],
            ],
        ]];

        yield 'orderCustomField rule' => [$conditionId, [
            [
                'id' => $conditionId,
                'type' => 'orderCustomField',
                'value' => [
                    'operator' => '=',
                    'selectedField' => Uuid::randomHex(),
                    'selectedFieldSet' => Uuid::randomHex(),
                    'renderedFieldValue' => 'string',
                    'renderedField' => [
                        'type' => 'text',
                    ],
                ],
            ],
        ]];

        yield 'customerCustomField rule' => [$conditionId, [
            [
                'id' => $conditionId,
                'type' => 'orderCustomField',
                'value' => [
                    'operator' => '=',
                    'selectedField' => Uuid::randomHex(),
                    'selectedFieldSet' => Uuid::randomHex(),
                    'renderedFieldValue' => 'string',
                    'renderedField' => [
                        'type' => 'text',
                    ],
                ],
            ],
        ]];
    }
}
