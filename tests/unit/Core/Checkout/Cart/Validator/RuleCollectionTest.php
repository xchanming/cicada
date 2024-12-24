<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Validator;

use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Rule\Container\OrRule;
use Cicada\Core\Framework\Rule\RuleCollection;
use Cicada\Core\Test\Stub\Rule\FalseRule;
use Cicada\Core\Test\Stub\Rule\TrueRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RuleCollection::class)]
class RuleCollectionTest extends TestCase
{
    public function testMetaCollecting(): void
    {
        $collection = new RuleCollection([
            new TrueRule(),
            new AndRule([
                new TrueRule(),
                new OrRule([
                    new TrueRule(),
                    new FalseRule(),
                ]),
            ]),
        ]);

        static::assertTrue($collection->has(FalseRule::class));
        static::assertTrue($collection->has(OrRule::class));
        static::assertEquals(
            new RuleCollection([
                new FalseRule(),
            ]),
            $collection->filterInstance(FalseRule::class)
        );
    }
}
