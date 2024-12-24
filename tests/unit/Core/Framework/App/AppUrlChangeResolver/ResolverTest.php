<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\AppUrlChangeResolver;

use Cicada\Core\Framework\App\AppUrlChangeResolver\AbstractAppUrlChangeStrategy;
use Cicada\Core\Framework\App\AppUrlChangeResolver\Resolver;
use Cicada\Core\Framework\App\Exception\AppUrlChangeStrategyNotFoundException;
use Cicada\Core\Framework\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Resolver::class)]
class ResolverTest extends TestCase
{
    private MockObject&AbstractAppUrlChangeStrategy $firstStrategy;

    private MockObject&AbstractAppUrlChangeStrategy $secondStrategy;

    private Resolver $appUrlChangedResolverStrategy;

    protected function setUp(): void
    {
        $this->firstStrategy = $this->createMock(AbstractAppUrlChangeStrategy::class);
        $this->firstStrategy->method('getName')
            ->willReturn('FirstStrategy');

        $this->secondStrategy = $this->createMock(AbstractAppUrlChangeStrategy::class);
        $this->secondStrategy->method('getName')
            ->willReturn('SecondStrategy');

        $this->appUrlChangedResolverStrategy = new Resolver([
            $this->firstStrategy,
            $this->secondStrategy,
        ]);
    }

    public function testItCallsRightStrategy(): void
    {
        $this->firstStrategy->expects(static::once())
            ->method('resolve');

        $this->secondStrategy->expects(static::never())
            ->method('resolve');

        $this->appUrlChangedResolverStrategy->resolve('FirstStrategy', Context::createDefaultContext());
    }

    public function testItThrowsOnUnknownStrategy(): void
    {
        $this->firstStrategy->expects(static::never())
            ->method('resolve');

        $this->secondStrategy->expects(static::never())
            ->method('resolve');

        $this->expectException(AppUrlChangeStrategyNotFoundException::class);
        $this->expectExceptionMessage('Unable to find AppUrlChangeResolver with name: "ThirdStrategy".');
        $this->appUrlChangedResolverStrategy->resolve('ThirdStrategy', Context::createDefaultContext());
    }

    public function testGetAvailableStrategies(): void
    {
        $this->firstStrategy->expects(static::once())
            ->method('getDescription')
            ->willReturn('first description');

        $this->secondStrategy->expects(static::once())
            ->method('getDescription')
            ->willReturn('second description');

        static::assertEquals([
            'FirstStrategy' => 'first description',
            'SecondStrategy' => 'second description',
        ], $this->appUrlChangedResolverStrategy->getAvailableStrategies());
    }
}
