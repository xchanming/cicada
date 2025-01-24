<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Rule\Container;

use Cicada\Core\Checkout\Cart\Rule\LineItemInCategoryRule;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Rule\Container\MatchAllLineItemsRule;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Tests\Unit\Core\Checkout\Cart\SalesChannel\Helper\CartRuleHelperTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MatchAllLineItemsRuleTest extends TestCase
{
    use CartRuleHelperTrait;
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<RuleCollection>
     */
    private EntityRepository $ruleRepository;

    private EntityRepository $conditionRepository;

    private Context $context;

    protected function setUp(): void
    {
        $this->ruleRepository = static::getContainer()->get('rule.repository');
        $this->conditionRepository = static::getContainer()->get('rule_condition.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testValidateWithInvalidRulesType(): void
    {
        try {
            $this->conditionRepository->create([
                [
                    'type' => (new MatchAllLineItemsRule())->getName(),
                    'ruleId' => Uuid::randomHex(),
                    'value' => [
                        'rules' => ['Rule'],
                    ],
                ],
            ], $this->context);
            static::fail('Exception was not thrown');
        } catch (WriteException $stackException) {
            $exceptions = iterator_to_array($stackException->getErrors());
            static::assertCount(1, $exceptions);
            static::assertSame('/0/value/rules', $exceptions[0]['source']['pointer']);
            static::assertSame(Type::INVALID_TYPE_ERROR, $exceptions[0]['code']);
        }
    }

    public function testValidateWithValidRulesType(): void
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
                'type' => (new MatchAllLineItemsRule())->getName(),
                'ruleId' => $ruleId,
            ],
        ], $this->context);

        static::assertNotNull($this->conditionRepository->search(new Criteria([$id]), $this->context)->get($id));

        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $id]], $this->context);
    }

    public function testValidateWithValidRulesTypeWithChildren(): void
    {
        $ruleId = Uuid::randomHex();
        $this->ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            $this->context
        );

        $id = Uuid::randomHex();
        $categoryIds = [Uuid::randomHex()];
        $this->conditionRepository->create([
            [
                'id' => $id,
                'type' => (new MatchAllLineItemsRule())->getName(),
                'ruleId' => $ruleId,
                'children' => [
                    [
                        'type' => (new LineItemInCategoryRule())->getName(),
                        'ruleId' => $ruleId,
                        'value' => [
                            'operator' => MatchAllLineItemsRule::OPERATOR_EQ,
                            'categoryIds' => $categoryIds,
                        ],
                    ],
                ],
            ],
        ], $this->context);

        static::assertNotNull($this->conditionRepository->search(new Criteria([$id]), $this->context)->get($id));
        $ruleStruct = $this->ruleRepository->search(new Criteria([$ruleId]), $this->context)->getEntities()->get($ruleId);
        static::assertInstanceOf(RuleEntity::class, $ruleStruct);
        static::assertEquals(new AndRule([new MatchAllLineItemsRule([new LineItemInCategoryRule(MatchAllLineItemsRule::OPERATOR_EQ, $categoryIds)])]), $ruleStruct->getPayload());

        $this->ruleRepository->delete([['id' => $ruleId]], $this->context);
        $this->conditionRepository->delete([['id' => $id]], $this->context);
    }
}
