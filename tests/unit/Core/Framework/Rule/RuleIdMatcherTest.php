<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Rule;

use Cicada\Core\Framework\DataAbstractionLayer\Contract\IdAware;
use Cicada\Core\Framework\DataAbstractionLayer\Contract\RuleIdAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\RuleIdMatcher;
use Cicada\Core\Framework\Struct\Collection;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @phpstan-import-type Option from RuleIdMatcher
 */
#[CoversClass(RuleIdMatcher::class)]
#[Package('framework')]
class RuleIdMatcherTest extends TestCase
{
    private readonly IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
    }

    public function testFilter(): void
    {
        $option1 = $this->createOption($this->ids->get('option1'), $this->ids->get('rule1'));
        $option2 = $this->createOption($this->ids->get('option2'), $this->ids->get('rule2'));
        $option3 = $this->createOption($this->ids->get('option3'), $this->ids->get('rule3'));

        $options = [$option1, $option2, $option3];

        $matcher = new RuleIdMatcher();

        $filtered = $matcher->filter($options, [$this->ids->get('rule2'), $this->ids->get('rule3')]);

        static::assertCount(2, $filtered);
        static::assertSame($this->ids->get('option2'), $filtered[0]->getId());
        static::assertSame($this->ids->get('option3'), $filtered[1]->getId());
    }

    public function testFilterWithNullAvailabilityRuleId(): void
    {
        $option1 = $this->createOption($this->ids->get('option1'), $this->ids->get('rule1'));
        $option2 = $this->createOption($this->ids->get('option2'));
        $option3 = $this->createOption($this->ids->get('option3'), $this->ids->get('rule3'));

        $options = [$option1, $option2, $option3];

        $matcher = new RuleIdMatcher();

        $filtered = $matcher->filter($options, [$this->ids->get('rule2'), $this->ids->get('rule3')]);

        static::assertCount(2, $filtered);
        static::assertSame($this->ids->get('option2'), $filtered[0]->getId());
        static::assertSame($this->ids->get('option3'), $filtered[1]->getId());
    }

    public function testFilterCollection(): void
    {
        $option1 = $this->createOption($this->ids->get('option1'), $this->ids->get('rule1'));
        $option2 = $this->createOption($this->ids->get('option2'), $this->ids->get('rule2'));
        $option3 = $this->createOption($this->ids->get('option3'), $this->ids->get('rule3'));

        $options = new class([$option1, $option2, $option3]) extends Collection {
        };

        $matcher = new RuleIdMatcher();

        /** @var Collection<IdAware&RuleIdAware> $filtered */
        $filtered = $matcher->filterCollection($options, [$this->ids->get('rule2'), $this->ids->get('rule3')]);

        static::assertCount(2, $filtered);
        static::assertSame($this->ids->get('option2'), $filtered->first()?->getId());
        static::assertSame($this->ids->get('option3'), $filtered->last()?->getId());
    }

    public function testFilterCollectionWithNullAvailabilityRuleId(): void
    {
        $option1 = $this->createOption($this->ids->get('option1'), $this->ids->get('rule1'));
        $option2 = $this->createOption($this->ids->get('option2'));
        $option3 = $this->createOption($this->ids->get('option3'), $this->ids->get('rule3'));

        $options = new class([$option1, $option2, $option3]) extends Collection {
        };

        $matcher = new RuleIdMatcher();

        /** @var Collection<IdAware&RuleIdAware> $filtered */
        $filtered = $matcher->filterCollection($options, [$this->ids->get('rule2'), $this->ids->get('rule3')]);

        static::assertCount(2, $filtered);
        static::assertSame($this->ids->get('option2'), $filtered->first()?->getId());
        static::assertSame($this->ids->get('option3'), $filtered->last()?->getId());
    }

    /**
     * @return (IdAware&RuleIdAware)
     */
    private function createOption(?string $id = null, ?string $ruleId = null): object
    {
        $id ??= Uuid::randomHex();

        return new class($id, $ruleId) implements IdAware, RuleIdAware {
            public function __construct(
                private readonly string $id,
                private readonly ?string $ruleId = null,
            ) {
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getAvailabilityRuleId(): ?string
            {
                return $this->ruleId;
            }
        };
    }
}
