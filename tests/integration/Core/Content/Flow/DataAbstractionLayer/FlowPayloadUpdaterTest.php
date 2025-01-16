<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Flow\DataAbstractionLayer;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Cart\Rule\AlwaysValidRule;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Content\Flow\Dispatching\Action\AddOrderTagAction;
use Cicada\Core\Content\Flow\Dispatching\Action\RemoveOrderTagAction;
use Cicada\Core\Content\Flow\Dispatching\Struct\ActionSequence;
use Cicada\Core\Content\Flow\Dispatching\Struct\Flow;
use Cicada\Core\Content\Flow\Dispatching\Struct\IfSequence;
use Cicada\Core\Content\Flow\FlowCollection;
use Cicada\Core\Content\Flow\FlowEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('after-sales')]
class FlowPayloadUpdaterTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<FlowCollection>
     */
    private EntityRepository $flowRepository;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->flowRepository = static::getContainer()->get('flow.repository');

        $this->ids = new IdsCollection();
    }

    public function testCreate(): void
    {
        $this->createTestData();

        $flow = $this->flowRepository->search(new Criteria([$this->ids->get('flow_id')]), Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($flow);

        $trueCaseNextAction = new ActionSequence();
        $trueCaseNextAction->action = AddOrderTagAction::getName();
        $trueCaseNextAction->config = [
            'tagId' => $this->ids->get('tag_id2'),
            'entity' => OrderDefinition::ENTITY_NAME,
        ];
        $trueCaseNextAction->flowId = $this->ids->get('flow_id');
        $trueCaseNextAction->sequenceId = $this->ids->get('flow_sequence_id2');

        $trueCase = new ActionSequence();
        $trueCase->action = AddOrderTagAction::getName();
        $trueCase->config = [
            'tagId' => $this->ids->get('tag_id'),
            'entity' => OrderDefinition::ENTITY_NAME,
        ];
        $trueCase->nextAction = $trueCaseNextAction;
        $trueCase->flowId = $this->ids->get('flow_id');
        $trueCase->sequenceId = $this->ids->get('flow_sequence_id1');

        $sequence = new IfSequence();
        $sequence->ruleId = $this->ids->get('rule_id');
        $sequence->trueCase = $trueCase;
        $sequence->flowId = $this->ids->get('flow_id');
        $sequence->sequenceId = $this->ids->get('flow_sequence_id');

        $flat = [];
        $flat[$this->ids->create('flow_sequence_id1')] = $trueCase;
        $flat[$this->ids->get('flow_sequence_id')] = $sequence;

        $expected = [$sequence];

        static::assertSame(serialize(new Flow($this->ids->get('flow_id'), $expected, $flat)), $flow->getPayload());
    }

    public function testUpdate(): void
    {
        $this->createTestData();

        $this->flowRepository->update([
            [
                'id' => $this->ids->get('flow_id'),
                'sequences' => [
                    [
                        'id' => $this->ids->create('flow_sequence_id1'),
                        'actionName' => RemoveOrderTagAction::getName(),
                    ],
                    [
                        'id' => $this->ids->create('flow_sequence_id2'),
                        'trueCase' => false,
                        'position' => 1,
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        $flow = $this->flowRepository->search(new Criteria([$this->ids->get('flow_id')]), Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($flow);

        $falseCase = new ActionSequence();
        $falseCase->action = AddOrderTagAction::getName();
        $falseCase->config = [
            'tagId' => $this->ids->get('tag_id2'),
            'entity' => OrderDefinition::ENTITY_NAME,
        ];
        $falseCase->flowId = $this->ids->get('flow_id');
        $falseCase->sequenceId = $this->ids->get('flow_sequence_id2');

        $trueCase = new ActionSequence();
        $trueCase->action = RemoveOrderTagAction::getName();
        $trueCase->config = [
            'tagId' => $this->ids->get('tag_id'),
            'entity' => OrderDefinition::ENTITY_NAME,
        ];
        $trueCase->flowId = $this->ids->get('flow_id');
        $trueCase->sequenceId = $this->ids->get('flow_sequence_id1');

        $sequence = new IfSequence();
        $sequence->ruleId = $this->ids->get('rule_id');
        $sequence->trueCase = $trueCase;
        $sequence->falseCase = $falseCase;
        $sequence->flowId = $this->ids->get('flow_id');
        $sequence->sequenceId = $this->ids->get('flow_sequence_id');

        $flat = [];
        $flat[$this->ids->create('flow_sequence_id1')] = $trueCase;
        $flat[$this->ids->create('flow_sequence_id2')] = $falseCase;
        $flat[$this->ids->get('flow_sequence_id')] = $sequence;

        $expected = [$sequence];

        static::assertSame(serialize(new Flow($this->ids->get('flow_id'), $expected, $flat)), $flow->getPayload());
    }

    public function testPayloadShouldUpdateAfterDeletedAllSequence(): void
    {
        $this->createTestData();

        $flowSequenceRepository = static::getContainer()->get('flow_sequence.repository');
        $flowSequenceRepository->delete([
            ['id' => $this->ids->get('flow_sequence_id2')],
            ['id' => $this->ids->get('flow_sequence_id1')],
            ['id' => $this->ids->get('flow_sequence_id')],
        ], Context::createDefaultContext());

        $criteria = new Criteria([$this->ids->get('flow_id')]);
        $criteria->addAssociation('sequences');
        $flow = $this->flowRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($flow);

        static::assertSame([], $flow->getSequences()?->getElements());
        static::assertSame(serialize(new Flow($this->ids->get('flow_id'), [])), $flow->getPayload());
    }

    public function testPayloadShouldBeCorrectWithoutSequence(): void
    {
        $this->flowRepository->create([[
            'id' => $this->ids->create('flow_id'),
            'name' => 'Create Order',
            'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            'priority' => 1,
            'active' => true,
            'payload' => null,
            'invalid' => true,
        ]], Context::createDefaultContext());

        $criteria = new Criteria([$this->ids->get('flow_id')]);
        $criteria->addAssociation('sequences');
        $flow = $this->flowRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($flow);

        static::assertSame([], $flow->getSequences()?->getElements());
        static::assertSame(serialize(new Flow($this->ids->get('flow_id'), [])), $flow->getPayload());
    }

    public function testJumpFlow(): void
    {
        $this->createTestData();

        $flowEntity = $this->flowRepository->search(new Criteria([$this->ids->get('flow_id')]), Context::createDefaultContext())->getEntities()->first();
        static::assertInstanceOf(FlowEntity::class, $flowEntity);

        $payload = $flowEntity->getPayload();
        static::assertIsString($payload);
        $flow = unserialize($payload);
        static::assertInstanceOf(Flow::class, $flow);
        static::assertInstanceOf(IfSequence::class, $flow->getSequences()[0]);

        $flat = $flow->getFlat();
        static::assertInstanceOf(ActionSequence::class, $flat[$this->ids->create('flow_sequence_id1')]);
        static::assertInstanceOf(IfSequence::class, $flat[$this->ids->create('flow_sequence_id')]);

        $flow->jump($this->ids->create('flow_sequence_id1'));
        static::assertInstanceOf(ActionSequence::class, $flow->getSequences()[0]);
    }

    private function createTestData(): void
    {
        $this->flowRepository->create([[
            'id' => $this->ids->create('flow_id'),
            'name' => 'Create Order',
            'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            'priority' => 1,
            'active' => true,
            'payload' => null,
            'invalid' => true,
            'sequences' => [
                [
                    'id' => $this->ids->create('flow_sequence_id'),
                    'parentId' => null,
                    'ruleId' => $this->ids->create('rule_id'),
                    'actionName' => null,
                    'config' => [],
                    'position' => 1,
                    'rule' => [
                        'id' => $this->ids->get('rule_id'),
                        'name' => 'Test rule',
                        'priority' => 1,
                        'conditions' => [
                            ['type' => (new AlwaysValidRule())->getName()],
                        ],
                    ],
                ],
                [
                    'id' => $this->ids->create('flow_sequence_id1'),
                    'parentId' => $this->ids->get('flow_sequence_id'),
                    'ruleId' => null,
                    'actionName' => AddOrderTagAction::getName(),
                    'config' => [
                        'tagId' => $this->ids->get('tag_id'),
                        'entity' => OrderDefinition::ENTITY_NAME,
                    ],
                    'position' => 1,
                    'trueCase' => true,
                ],
                [
                    'id' => $this->ids->create('flow_sequence_id2'),
                    'parentId' => $this->ids->get('flow_sequence_id'),
                    'ruleId' => null,
                    'actionName' => AddOrderTagAction::getName(),
                    'config' => [
                        'tagId' => $this->ids->get('tag_id2'),
                        'entity' => OrderDefinition::ENTITY_NAME,
                    ],
                    'position' => 2,
                    'trueCase' => true,
                ],
            ],
        ]], Context::createDefaultContext());
    }
}
