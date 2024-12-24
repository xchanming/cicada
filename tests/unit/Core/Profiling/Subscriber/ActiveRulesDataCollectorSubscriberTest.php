<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Profiling\Subscriber;

use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Routing\Event\SalesChannelContextResolvedEvent;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Profiling\Subscriber\ActiveRulesDataCollectorSubscriber;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[CoversClass(ActiveRulesDataCollectorSubscriber::class)]
class ActiveRulesDataCollectorSubscriberTest extends TestCase
{
    public function testEvents(): void
    {
        static::assertSame(
            [
                SalesChannelContextResolvedEvent::class => 'onContextResolved',
            ],
            ActiveRulesDataCollectorSubscriber::getSubscribedEvents()
        );
    }

    public function testDataCollection(): void
    {
        $ruleId = Uuid::randomHex();

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $context = new Context(new SystemSource(), [$ruleId]);
        $salesChannelContext->method('getContext')->willReturn($context);
        $event = new SalesChannelContextResolvedEvent($salesChannelContext, Uuid::randomHex());

        $activeRule = new RuleEntity();
        $activeRule->setId($ruleId);
        $activeRule->setName('Demo rule');
        $activeRule->setPriority(100);

        $ruleRepository = $this->createMock(EntityRepository::class);
        $ruleRepository
            ->method('search')
            ->willReturn(new EntitySearchResult(
                'rule',
                1,
                new RuleCollection([$activeRule]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            ));

        $subscriber = new ActiveRulesDataCollectorSubscriber($ruleRepository);
        $subscriber->onContextResolved($event);
        $subscriber->collect(new Request(), new Response());

        $data = $subscriber->getData();

        static::assertEquals(1, $subscriber->getMatchingRuleCount());
        static::assertArrayHasKey($ruleId, $data);

        $rule = $data[$ruleId];
        static::assertInstanceOf(RuleEntity::class, $rule);
        static::assertEquals(100, $rule->getPriority());
        static::assertEquals('Demo rule', $rule->getName());

        $subscriber->reset();

        static::assertEquals(0, $subscriber->getMatchingRuleCount());
    }

    public function testEmptyRuleIds(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $context = new Context(new SystemSource(), []);
        $salesChannelContext->method('getContext')->willReturn($context);
        $event = new SalesChannelContextResolvedEvent($salesChannelContext, Uuid::randomHex());

        $ruleRepository = $this->createMock(EntityRepository::class);
        $ruleRepository
            ->expects(static::never())
            ->method('search');

        $subscriber = new ActiveRulesDataCollectorSubscriber($ruleRepository);
        $subscriber->onContextResolved($event);
        $subscriber->collect(new Request(), new Response());
    }

    public function testTemplate(): void
    {
        static::assertEquals('@Profiling/Collector/rules.html.twig', ActiveRulesDataCollectorSubscriber::getTemplate());
    }
}
