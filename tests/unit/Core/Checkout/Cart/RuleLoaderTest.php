<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Rule\AlwaysValidRule;
use Cicada\Core\Checkout\Cart\RuleLoader;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RuleLoader::class)]
class RuleLoaderTest extends TestCase
{
    public function testDecorated(): void
    {
        $this->expectException(DecorationPatternException::class);
        $this->expectExceptionMessage(\sprintf(
            'The getDecorated() function of core class %s cannot be used. This class is the base class.',
            RuleLoader::class,
        ));
        /** @var StaticEntityRepository<RuleCollection> $ruleRepository */
        $ruleRepository = new StaticEntityRepository([], new RuleDefinition());
        $ruleLoader = new RuleLoader($ruleRepository);
        $ruleLoader->getDecorated();
    }

    public function testLoad(): void
    {
        /** @var StaticEntityRepository<RuleCollection> $ruleRepository */
        $ruleRepository = new StaticEntityRepository(
            [
                function (Criteria $criteria): RuleCollection {
                    static::assertSame(500, $criteria->getLimit());
                    static::assertSame('cart-rule-loader::load-rules', $criteria->getTitle());
                    static::assertCount(2, $criteria->getSorting());
                    static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
                    static::assertSame('invalid', $criteria->getFilters()[0]->getField());
                    static::assertFalse($criteria->getFilters()[0]->getValue());

                    return $this->getRuleCollection(500);
                },
                $this->getRuleCollection(1),
            ],
            new RuleDefinition(),
        );

        $ruleLoader = new RuleLoader($ruleRepository);
        $rules = $ruleLoader->load(Context::createDefaultContext());

        static::assertCount(501, $rules);
    }

    public function testLoadWithoutSecondResult(): void
    {
        /** @var StaticEntityRepository<RuleCollection> $ruleRepository */
        $ruleRepository = new StaticEntityRepository(
            [
                function (Criteria $criteria): RuleCollection {
                    static::assertSame(500, $criteria->getLimit());
                    static::assertSame('cart-rule-loader::load-rules', $criteria->getTitle());
                    static::assertCount(2, $criteria->getSorting());
                    static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
                    static::assertSame('invalid', $criteria->getFilters()[0]->getField());
                    static::assertFalse($criteria->getFilters()[0]->getValue());

                    return $this->getRuleCollection(500);
                },
                $this->getRuleCollection(0),
            ],
            new RuleDefinition(),
        );

        $ruleLoader = new RuleLoader($ruleRepository);
        $rules = $ruleLoader->load(Context::createDefaultContext());

        static::assertCount(500, $rules);
    }

    private function getRuleCollection(int $count): RuleCollection
    {
        $ruleCollection = new RuleCollection();

        for ($i = 0; $i < $count; ++$i) {
            $rule = new RuleEntity();
            $rule->setId(Uuid::randomHex());
            $rule->setPayload(new AlwaysValidRule());
            $ruleCollection->add($rule);
        }

        return $ruleCollection;
    }
}
