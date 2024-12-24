<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching;

use Cicada\Core\Checkout\Cart\AbstractRuleLoader;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Dispatching\Action\AddCustomerTagAction;
use Cicada\Core\Content\Flow\Dispatching\Action\AddOrderTagAction;
use Cicada\Core\Content\Flow\Dispatching\Action\FlowAction;
use Cicada\Core\Content\Flow\Dispatching\Action\StopFlowAction;
use Cicada\Core\Content\Flow\Dispatching\FlowExecutor;
use Cicada\Core\Content\Flow\Dispatching\FlowState;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Struct\ActionSequence;
use Cicada\Core\Content\Flow\Dispatching\Struct\Flow;
use Cicada\Core\Content\Flow\Dispatching\Struct\IfSequence;
use Cicada\Core\Content\Flow\Dispatching\Struct\Sequence;
use Cicada\Core\Content\Flow\Dispatching\TransactionalAction;
use Cicada\Core\Content\Flow\Dispatching\TransactionFailedException;
use Cicada\Core\Content\Flow\Exception\ExecuteSequenceException;
use Cicada\Core\Content\Flow\Extension\FlowExecutorExtension;
use Cicada\Core\Content\Flow\FlowException;
use Cicada\Core\Content\Flow\Rule\FlowRuleScope;
use Cicada\Core\Content\Flow\Rule\FlowRuleScopeBuilder;
use Cicada\Core\Content\Flow\Rule\OrderTagRule;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\App\Event\AppFlowActionEvent;
use Cicada\Core\Framework\App\Flow\Action\AppFlowActionProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RuleAreas;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Extensions\ExtensionDispatcher;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Tag\TagCollection;
use Cicada\Core\System\Tag\TagEntity;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception as DbalPdoException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(FlowExecutor::class)]
class FlowExecutorTest extends TestCase
{
    private const ACTION_ADD_ORDER_TAG = 'action.add.order.tag';
    private const ACTION_ADD_CUSTOMER_TAG = 'action.add.customer.tag';
    private const ACTION_STOP_FLOW = 'action.stop.flow';

    /**
     * @param array<int, mixed> $actionSequencesExecuted
     * @param array<int, mixed> $actionSequencesTrueCase
     * @param array<int, mixed> $actionSequencesFalseCase
     *
     * @throws ExecuteSequenceException
     */
    #[DataProvider('actionsProvider')]
    public function testExecute(array $actionSequencesExecuted, array $actionSequencesTrueCase, array $actionSequencesFalseCase, ?string $appAction = null): void
    {
        $ids = new IdsCollection();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $appFlowActionProvider = $this->createMock(AppFlowActionProvider::class);
        $ruleLoader = $this->createMock(AbstractRuleLoader::class);
        $scopeBuilder = $this->createMock(FlowRuleScopeBuilder::class);
        $connection = $this->createMock(Connection::class);

        $addOrderTagAction = $this->createMock(AddOrderTagAction::class);
        $addCustomerTagAction = $this->createMock(AddCustomerTagAction::class);
        $stopFlowAction = $this->createMock(StopFlowAction::class);
        $actions = [
            self::ACTION_ADD_ORDER_TAG => $addOrderTagAction,
            self::ACTION_ADD_CUSTOMER_TAG => $addCustomerTagAction,
            self::ACTION_STOP_FLOW => $stopFlowAction,
        ];

        $actionSequences = [];
        if ($actionSequencesExecuted !== []) {
            foreach ($actionSequencesExecuted as $actionSequenceExecuted) {
                $actionSequence = new ActionSequence();
                $actionSequence->sequenceId = $ids->get($actionSequenceExecuted);
                $actionSequence->action = $actionSequenceExecuted;

                $actionSequences[] = $actionSequence;
            }
        }

        $context = Context::createDefaultContext();
        if ($actionSequencesTrueCase !== []) {
            $condition = new IfSequence();
            $condition->sequenceId = $ids->get('true_case');
            $condition->ruleId = $ids->get('ruleId');

            $context = Context::createDefaultContext();
            $context->setRuleIds([$ids->get('ruleId')]);

            foreach ($actionSequencesTrueCase as $actionSequenceTrueCase) {
                $actionSequence = new ActionSequence();
                $actionSequence->sequenceId = $ids->get($actionSequenceTrueCase);
                $actionSequence->action = $actionSequenceTrueCase;

                $condition->trueCase = $actionSequence;
            }

            $actionSequences[] = $condition;
        }

        if ($actionSequencesFalseCase !== []) {
            $condition = new IfSequence();
            $condition->sequenceId = $ids->get('false_case');
            $condition->ruleId = $ids->get('ruleId');

            $context = Context::createDefaultContext();

            foreach ($actionSequencesFalseCase as $actionSequenceFalseCase) {
                $actionSequence = new ActionSequence();
                $actionSequence->sequenceId = $ids->get($actionSequenceFalseCase);
                $actionSequence->action = $actionSequenceFalseCase;

                $condition->falseCase = $actionSequence;
            }

            $actionSequences[] = $condition;
        }

        if ($appAction) {
            $appActionSequence = new ActionSequence();
            $appActionSequence->appFlowActionId = $ids->get('AppActionId');
            $appActionSequence->sequenceId = $ids->get('AppActionSequenceId');
            $appActionSequence->action = 'app.action';
            $appFlowActionProvider->expects(static::once())->method('getWebhookPayloadAndHeaders')->willReturn([
                'headers' => [],
                'payload' => [],
            ]);
            $eventDispatcher->expects(static::once())->method('dispatch')->with(
                new AppFlowActionEvent('app.action', [], []),
                'app.action'
            );
            $actionSequences[] = $appActionSequence;
        }

        $flow = new Flow($ids->get('flowId'), $actionSequences);

        $storableFlow = new StorableFlow('', $context);

        if (\in_array(self::ACTION_ADD_ORDER_TAG, array_merge_recursive($actionSequencesExecuted, $actionSequencesTrueCase, $actionSequencesFalseCase), true)) {
            $addOrderTagAction->expects(static::once())->method('handleFlow')->with($storableFlow);
        } else {
            $addOrderTagAction->expects(static::never())->method('handleFlow');
        }

        if (\in_array(self::ACTION_ADD_CUSTOMER_TAG, array_merge_recursive($actionSequencesExecuted, $actionSequencesTrueCase, $actionSequencesFalseCase), true)) {
            $addCustomerTagAction->expects(static::once())->method('handleFlow')->with($storableFlow);
        } else {
            $addCustomerTagAction->expects(static::never())->method('handleFlow');
        }

        if (\in_array(self::ACTION_STOP_FLOW, array_merge_recursive($actionSequencesExecuted, $actionSequencesTrueCase, $actionSequencesFalseCase), true)) {
            $stopFlowAction->expects(static::once())->method('handleFlow')->with($storableFlow);
        } else {
            $stopFlowAction->expects(static::never())->method('handleFlow');
        }

        $flowExecutor = new FlowExecutor($eventDispatcher, $appFlowActionProvider, $ruleLoader, $scopeBuilder, $connection, new ExtensionDispatcher(new EventDispatcher()), $actions);
        $flowExecutor->execute($flow, $storableFlow);
    }

    public static function actionsProvider(): \Generator
    {
        yield 'Single action executed' => [
            [
                self::ACTION_ADD_ORDER_TAG,
            ],
            [],
            [],
        ];

        yield 'Multiple actions executed' => [
            [
                self::ACTION_ADD_ORDER_TAG,
                self::ACTION_ADD_CUSTOMER_TAG,
                self::ACTION_STOP_FLOW,
            ],
            [],
            [],
        ];

        yield 'Action executed with true case' => [
            [],
            [
                self::ACTION_ADD_ORDER_TAG,
            ],
            [],
        ];

        yield 'Action executed with false case' => [
            [],
            [],
            [
                self::ACTION_ADD_ORDER_TAG,
            ],
        ];

        yield 'Action executed from App' => [
            [],
            [],
            [],
            'app.action',
        ];
    }

    public function testExecuteIfWithRuleEvaluation(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $appFlowActionProvider = $this->createMock(AppFlowActionProvider::class);
        $ruleLoader = $this->createMock(AbstractRuleLoader::class);
        $scopeBuilder = $this->createMock(FlowRuleScopeBuilder::class);
        $connection = $this->createMock(Connection::class);

        $trueCaseSequence = new Sequence();
        $trueCaseSequence->assign(['sequenceId' => 'foobar']);
        $ruleId = Uuid::randomHex();
        $ifSequence = new IfSequence();
        $ifSequence->assign(['ruleId' => $ruleId, 'trueCase' => $trueCaseSequence]);

        $order = new OrderEntity();
        $tagId = Uuid::randomHex();
        $tag = new TagEntity();
        $tag->setId($tagId);
        $order->setTags(new TagCollection([$tag]));

        $flow = new StorableFlow('bar', Context::createDefaultContext());
        $flow->setFlowState(new FlowState());
        $flow->setData(OrderAware::ORDER, $order);

        $scopeBuilder->method('build')->willReturn(
            new FlowRuleScope($order, new Cart('test'), $this->createMock(SalesChannelContext::class))
        );

        $rule = new OrderTagRule(Rule::OPERATOR_EQ, [$tagId]);
        $ruleEntity = new RuleEntity();
        $ruleEntity->setId($ruleId);
        $ruleEntity->setPayload($rule);
        $ruleEntity->setAreas([RuleAreas::FLOW_AREA]);
        $ruleLoader->method('load')->willReturn(new RuleCollection([$ruleEntity]));

        $flowExecutor = new FlowExecutor($eventDispatcher, $appFlowActionProvider, $ruleLoader, $scopeBuilder, $connection, new ExtensionDispatcher(new EventDispatcher()), []);
        $flowExecutor->executeIf($ifSequence, $flow);

        static::assertEquals($trueCaseSequence, $flow->getFlowState()->currentSequence);
    }

    public function testActionExecutedInTransactionWhenItImplementsTransactional(): void
    {
        $ids = new IdsCollection();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $appFlowActionProvider = $this->createMock(AppFlowActionProvider::class);
        $ruleLoader = $this->createMock(AbstractRuleLoader::class);
        $scopeBuilder = $this->createMock(FlowRuleScopeBuilder::class);
        $connection = $this->createMock(Connection::class);

        $action = new class extends FlowAction implements TransactionalAction {
            public bool $handled = false;

            public function requirements(): array
            {
                return [];
            }

            public function handleFlow(StorableFlow $flow): void
            {
                $this->handled = true;
            }

            public static function getName(): string
            {
                return 'transactional-action';
            }
        };

        $actionSequence = new ActionSequence();
        $actionSequence->sequenceId = $ids->get($action::class);
        $actionSequence->action = $action::class;

        $connection->expects(static::once())
            ->method('beginTransaction');

        $connection->expects(static::once())
            ->method('commit');

        $flow = new StorableFlow('some-flow', Context::createDefaultContext());
        $flow->setFlowState(new FlowState());

        $flowExecutor = new FlowExecutor($eventDispatcher, $appFlowActionProvider, $ruleLoader, $scopeBuilder, $connection, new ExtensionDispatcher(new EventDispatcher()), [
            $action::class => $action,
        ]);
        $flowExecutor->executeAction($actionSequence, $flow);

        static::assertTrue($action->handled);
    }

    public function testTransactionCommitFailureExceptionIsWrapped(): void
    {
        $ids = new IdsCollection();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $appFlowActionProvider = $this->createMock(AppFlowActionProvider::class);
        $ruleLoader = $this->createMock(AbstractRuleLoader::class);
        $scopeBuilder = $this->createMock(FlowRuleScopeBuilder::class);
        $connection = $this->createMock(Connection::class);

        $action = new class extends FlowAction implements TransactionalAction {
            public function requirements(): array
            {
                return [];
            }

            public function handleFlow(StorableFlow $flow): void
            {
            }

            public static function getName(): string
            {
                return 'transactional-action';
            }
        };

        $actionSequence = new ActionSequence();
        $actionSequence->sequenceId = $ids->get($action::class);
        $actionSequence->action = $action::class;

        $connection->expects(static::once())
            ->method('beginTransaction');

        $e = new TableNotFoundException(
            new DbalPdoException('Table not found', null, 1146),
            null
        );

        $connection->expects(static::once())
            ->method('commit')
            ->willThrowException($e);

        $connection->expects(static::once())
            ->method('rollBack');

        $flow = new StorableFlow('some-flow', Context::createDefaultContext());
        $flow->setFlowState(new FlowState());

        $flowExecutor = new FlowExecutor($eventDispatcher, $appFlowActionProvider, $ruleLoader, $scopeBuilder, $connection, new ExtensionDispatcher(new EventDispatcher()), [
            $action::class => $action,
        ]);

        try {
            $flowExecutor->executeAction($actionSequence, $flow);
            static::fail(FlowException::class . ' should be thrown');
        } catch (FlowException $e) {
            static::assertSame(FlowException::FLOW_ACTION_TRANSACTION_COMMIT_FAILED, $e->getErrorCode());
            static::assertSame('An exception occurred in the driver: Table not found', $e->getPrevious()?->getMessage());
        }
    }

    public function testTransactionAbortExceptionIsWrapped(): void
    {
        $ids = new IdsCollection();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $appFlowActionProvider = $this->createMock(AppFlowActionProvider::class);
        $ruleLoader = $this->createMock(AbstractRuleLoader::class);
        $scopeBuilder = $this->createMock(FlowRuleScopeBuilder::class);
        $connection = $this->createMock(Connection::class);

        $action = new class extends FlowAction implements TransactionalAction {
            public function requirements(): array
            {
                return [];
            }

            public function handleFlow(StorableFlow $flow): void
            {
                throw TransactionFailedException::because(new \Exception('broken'));
            }

            public static function getName(): string
            {
                return 'transactional-action';
            }
        };

        $actionSequence = new ActionSequence();
        $actionSequence->sequenceId = $ids->get($action::class);
        $actionSequence->action = $action::class;

        $connection->expects(static::once())
            ->method('beginTransaction');

        $connection->expects(static::once())
            ->method('rollBack');

        $flow = new StorableFlow('some-flow', Context::createDefaultContext());
        $flow->setFlowState(new FlowState());

        $flowExecutor = new FlowExecutor($eventDispatcher, $appFlowActionProvider, $ruleLoader, $scopeBuilder, $connection, new ExtensionDispatcher(new EventDispatcher()), [
            $action::class => $action,
        ]);

        try {
            $flowExecutor->executeAction($actionSequence, $flow);
            static::fail(FlowException::class . ' should be thrown');
        } catch (FlowException $e) {
            static::assertSame(FlowException::FLOW_ACTION_TRANSACTION_ABORTED, $e->getErrorCode());
            static::assertSame('Transaction failed because an exception occurred. Exception: broken', $e->getPrevious()?->getMessage());
        }
    }

    public function testTransactionWithUncaughtExceptionIsWrapped(): void
    {
        $ids = new IdsCollection();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $appFlowActionProvider = $this->createMock(AppFlowActionProvider::class);
        $ruleLoader = $this->createMock(AbstractRuleLoader::class);
        $scopeBuilder = $this->createMock(FlowRuleScopeBuilder::class);
        $connection = $this->createMock(Connection::class);

        $action = new class extends FlowAction implements TransactionalAction {
            public function requirements(): array
            {
                return [];
            }

            public function handleFlow(StorableFlow $flow): void
            {
                /** @phpstan-ignore-next-line  */
                throw new \Exception('broken');
            }

            public static function getName(): string
            {
                return 'transactional-action';
            }
        };

        $actionSequence = new ActionSequence();
        $actionSequence->sequenceId = $ids->get($action::class);
        $actionSequence->action = $action::class;

        $connection->expects(static::once())
            ->method('beginTransaction');

        $connection->expects(static::once())
            ->method('rollBack');

        $flow = new StorableFlow('some-flow', Context::createDefaultContext());
        $flow->setFlowState(new FlowState());

        $flowExecutor = new FlowExecutor($eventDispatcher, $appFlowActionProvider, $ruleLoader, $scopeBuilder, $connection, new ExtensionDispatcher(new EventDispatcher()), [
            $action::class => $action,
        ]);

        try {
            $flowExecutor->executeAction($actionSequence, $flow);
            static::fail(FlowException::class . ' should be thrown');
        } catch (FlowException $e) {
            static::assertSame(FlowException::FLOW_ACTION_TRANSACTION_UNCAUGHT_EXCEPTION, $e->getErrorCode());
            static::assertSame('broken', $e->getPrevious()?->getMessage());
        }
    }

    public function testExtensionIsDispatched(): void
    {
        $dispatcher = new EventDispatcher();

        $executor = new FlowExecutor(
            $dispatcher,
            $this->createMock(AppFlowActionProvider::class),
            $this->createMock(AbstractRuleLoader::class),
            $this->createMock(FlowRuleScopeBuilder::class),
            $this->createMock(Connection::class),
            new ExtensionDispatcher($dispatcher),
            []
        );

        $pre = $this->createMock(CallableClass::class);
        $pre->expects(static::once())->method('__invoke');

        $post = $this->createMock(CallableClass::class);
        $post->expects(static::once())->method('__invoke');

        $dispatcher->addListener(FlowExecutorExtension::NAME . '.pre', $pre);
        $dispatcher->addListener(FlowExecutorExtension::NAME . '.post', $post);

        $executor->execute(new Flow('test', []), new StorableFlow('', Context::createDefaultContext()));
    }
}
