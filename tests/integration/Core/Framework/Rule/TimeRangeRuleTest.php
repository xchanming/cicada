<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Rule;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionCollection;
use Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\TimeRangeRule;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('services-settings')]
class TimeRangeRuleTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testIfRuleIsConsistent(): void
    {
        $ruleId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $ruleRepository = static::getContainer()->get('rule.repository');
        /** @var EntityRepository<RuleConditionCollection> $conditionRepository */
        $conditionRepository = static::getContainer()->get('rule_condition.repository');

        $ruleRepository->create(
            [['id' => $ruleId, 'name' => 'Demo rule', 'priority' => 1]],
            $context
        );

        $id = Uuid::randomHex();
        $conditionRepository->create([
            [
                'id' => $id,
                'type' => (new TimeRangeRule())->getName(),
                'ruleId' => $ruleId,
                'value' => [
                    'fromTime' => '15:00',
                    'toTime' => '12:00',
                ],
            ],
        ], $context);

        $result = $conditionRepository->search(new Criteria([$id]), $context)
            ->getEntities()
            ->get($id);

        static::assertInstanceOf(RuleConditionEntity::class, $result);
        $value = $result->getValue();
        static::assertIsArray($value);
        static::assertArrayHasKey('toTime', $value);
        static::assertArrayHasKey('fromTime', $value);
        static::assertEquals('12:00', $value['toTime']);
        static::assertEquals('15:00', $value['fromTime']);

        $ruleRepository->delete([['id' => $ruleId]], $context);
        $conditionRepository->delete([['id' => $id]], $context);
    }
}
