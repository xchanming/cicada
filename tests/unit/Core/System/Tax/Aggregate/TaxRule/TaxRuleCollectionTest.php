<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\Tax\Aggregate\TaxRule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tax\Aggregate\TaxRule\TaxRuleCollection;
use Cicada\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity;
use Cicada\Core\System\Tax\Aggregate\TaxRuleType\TaxRuleTypeEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TaxRuleCollection::class)]
#[Package('checkout')]
class TaxRuleCollectionTest extends TestCase
{
    public function testLatestActivationDate(): void
    {
        $rule1 = new TaxRuleEntity();
        $rule1->setId('rule1');
        $rule1->setActiveFrom(new \DateTime('2020-01-01'));

        $rule2 = new TaxRuleEntity();
        $rule2->setId('rule2');
        $rule2->setActiveFrom(new \DateTime('2020-01-02'));

        $rule3 = new TaxRuleEntity();
        $rule3->setId('rule3');

        $collection = new TaxRuleCollection([
            $rule1,
            $rule2,
            $rule3,
        ]);

        static::assertEquals(
            $rule2,
            $collection->latestActivationDate()
        );
    }

    public function testHighestTypePosition(): void
    {
        $rule1 = new TaxRuleEntity();
        $rule1->setId('rule1');
        $rule1->setType((new TaxRuleTypeEntity())->assign(['position' => 2]));

        $rule2 = new TaxRuleEntity();
        $rule2->setId('rule2');
        $rule2->setType((new TaxRuleTypeEntity())->assign(['position' => 1]));

        $collection = new TaxRuleCollection([
            $rule1,
            $rule2,
        ]);

        static::assertEquals(
            $rule2,
            $collection->highestTypePosition()
        );
    }

    public function testFilterByTypePosition(): void
    {
        $rule1 = new TaxRuleEntity();
        $rule1->setId('rule1');
        $rule1->setType((new TaxRuleTypeEntity())->assign(['position' => 2]));

        $rule2 = new TaxRuleEntity();
        $rule2->setId('rule2');
        $rule2->setType((new TaxRuleTypeEntity())->assign(['position' => 1]));

        $rule3 = new TaxRuleEntity();
        $rule3->setId('rule3');
        $rule3->setType((new TaxRuleTypeEntity())->assign(['position' => 2]));

        $collection = new TaxRuleCollection([
            $rule1,
            $rule2,
            $rule3,
        ]);

        static::assertEquals(
            ['rule1', 'rule3'],
            \array_values($collection->filterByTypePosition(2)->getIds())
        );
    }
}
