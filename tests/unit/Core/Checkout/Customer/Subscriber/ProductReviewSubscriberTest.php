<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\Service\ProductReviewCountService;
use Cicada\Core\Checkout\Customer\Subscriber\ProductReviewSubscriber;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityDeleteEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSet;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(ProductReviewSubscriber::class)]
class ProductReviewSubscriberTest extends TestCase
{
    private MockObject&ProductReviewCountService $productReviewCountService;

    private ProductReviewSubscriber $productReviewSubscriber;

    private StaticDefinitionInstanceRegistry $definitionInstanceRegistry;

    protected function setUp(): void
    {
        $this->productReviewCountService = $this->createMock(ProductReviewCountService::class);
        $this->productReviewSubscriber = new ProductReviewSubscriber($this->productReviewCountService);

        $this->definitionInstanceRegistry = new StaticDefinitionInstanceRegistry(
            [ProductReviewDefinition::class, ProductDefinition::class],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertEquals([
            'product_review.written' => 'createReview',
            EntityDeleteEvent::class => 'detectChangeset',
            'product_review.deleted' => 'onReviewDeleted',
        ], $this->productReviewSubscriber->getSubscribedEvents());
    }

    public function testDetectChangesetWithReviewDeleteEvent(): void
    {
        $ids = new IdsCollection();

        $event = EntityDeleteEvent::create(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new DeleteCommand(
                    $this->definitionInstanceRegistry->get(ProductReviewDefinition::class),
                    [
                        'id' => $ids->getBytes('foo'),
                    ],
                    new EntityExistence(ProductReviewDefinition::ENTITY_NAME, ['id' => $ids->get('foo')], true, false, false, [])
                ),
            ]
        );

        foreach ($event->getCommands() as $command) {
            static::assertInstanceOf(ChangeSetAware::class, $command);
            static::assertFalse($command->requiresChangeSet());
        }

        $this->productReviewSubscriber->detectChangeset($event);

        foreach ($event->getCommands() as $command) {
            static::assertInstanceOf(ChangeSetAware::class, $command);
            static::assertTrue($command->requiresChangeSet());
        }
    }

    public function testDetectChangesetWithInvalidCommands(): void
    {
        $ids = new IdsCollection();

        $event = EntityDeleteEvent::create(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new DeleteCommand(
                    $this->definitionInstanceRegistry->get(ProductDefinition::class),
                    [
                        'id' => $ids->getBytes('foo'),
                    ],
                    new EntityExistence(ProductDefinition::ENTITY_NAME, ['id' => $ids->get('foo')], true, false, false, [])
                ),
                new InsertCommand(
                    $this->definitionInstanceRegistry->get(ProductReviewDefinition::class),
                    ['id' => $ids->getBytes('foo')],
                    ['id' => $ids->getBytes('foo')],
                    new EntityExistence(ProductReviewDefinition::ENTITY_NAME, ['id' => $ids->get('foo')], true, false, false, []),
                    '/bar'
                ),
            ]
        );

        foreach ($event->getCommands() as $command) {
            static::assertInstanceOf(ChangeSetAware::class, $command);
            static::assertFalse($command->requiresChangeSet());
        }

        $this->productReviewSubscriber->detectChangeset($event);

        foreach ($event->getCommands() as $command) {
            static::assertInstanceOf(ChangeSetAware::class, $command);
            static::assertFalse($command->requiresChangeSet());
        }
    }

    public function testOnReviewDeleted(): void
    {
        $event = new EntityDeletedEvent(
            ProductReviewDefinition::ENTITY_NAME,
            [
                new EntityWriteResult(
                    'id',
                    ['id' => 'id'],
                    ProductReviewDefinition::ENTITY_NAME,
                    EntityWriteResult::OPERATION_DELETE,
                    new EntityExistence(ProductReviewDefinition::ENTITY_NAME, ['id' => 'id'], true, false, false, []),
                    new ChangeSet(['customer_id' => 'customer_id'], [], true)
                ),
                // should not trigger update as it has empty changeset
                new EntityWriteResult(
                    'id',
                    ['id' => 'id'],
                    ProductReviewDefinition::ENTITY_NAME,
                    EntityWriteResult::OPERATION_DELETE,
                    new EntityExistence(ProductReviewDefinition::ENTITY_NAME, ['id' => 'id'], true, false, false, []),
                    new ChangeSet([], [], true)
                ),
                // should not trigger update as it has wrong entity
                new EntityWriteResult(
                    'id',
                    ['id' => 'id'],
                    ProductDefinition::ENTITY_NAME,
                    EntityWriteResult::OPERATION_DELETE,
                    new EntityExistence(ProductDefinition::ENTITY_NAME, ['id' => 'id'], true, false, false, []),
                    new ChangeSet(['customer_id' => 'customer_id'], [], true)
                ),
            ],
            Context::createDefaultContext(),
        );

        $this->productReviewCountService->expects(static::once())
            ->method('updateReviewCountForCustomer')
            ->with('customer_id');

        $this->productReviewSubscriber->onReviewDeleted($event);
    }

    public function testCreateReviewWithInvalidEntityName(): void
    {
        $ids = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];
        $this->productReviewCountService->expects(static::never())->method('updateReviewCount');
        $this->productReviewSubscriber->createReview($this->getEntityWrittenEvent($ids, true));
    }

    public function testCreateReview(): void
    {
        $ids = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];
        $this->productReviewCountService->expects(static::once())->method('updateReviewCount')->with($ids);

        $this->productReviewSubscriber->createReview($this->getEntityWrittenEvent($ids));
    }

    /**
     * @param string[] $ids
     */
    private function getEntityWrittenEvent(array $ids = [], bool $invalidEntity = false): EntityWrittenEvent
    {
        $entity = $invalidEntity ? ProductDefinition::ENTITY_NAME : ProductReviewDefinition::ENTITY_NAME;

        $writtenResults = [];
        foreach ($ids as $id) {
            $writtenResult = $this->createMock(EntityWriteResult::class);
            $writtenResult->method('getPrimaryKey')->willReturn($id);
            $writtenResults[] = $writtenResult;
        }

        return new EntityWrittenEvent($entity, $writtenResults, Context::createDefaultContext());
    }
}
