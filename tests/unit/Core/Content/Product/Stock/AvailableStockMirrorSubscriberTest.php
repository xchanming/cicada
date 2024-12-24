<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Stock;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\Stock\AvailableStockMirrorSubscriber;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWriteEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(AvailableStockMirrorSubscriber::class)]
class AvailableStockMirrorSubscriberTest extends TestCase
{
    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
    }

    public function getDefinition(): ProductDefinition
    {
        new StaticDefinitionInstanceRegistry(
            [$definition = new ProductDefinition()],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        return $definition;
    }

    public function testBeforeWriteOnlyReactsToLiveVersions(): void
    {
        $context = Context::createDefaultContext()->createWithVersionId($this->ids->create('version'));

        $subscriber = new AvailableStockMirrorSubscriber();

        $definition = $this->getDefinition();

        $command = new UpdateCommand(
            $definition,
            ['stock' => 10],
            ['id' => $this->ids->getBytes('product-1')],
            new EntityExistence(
                ProductDefinition::ENTITY_NAME,
                ['id' => $this->ids->get('product-1')],
                true,
                false,
                false,
                []
            ),
            '/0'
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext($context),
            [$command],
        );

        $subscriber->__invoke($event);

        static::assertFalse($command->hasField('available_stock'));
    }

    public function testThatDeleteCommandIsIgnored(): void
    {
        $context = Context::createDefaultContext();

        $subscriber = new AvailableStockMirrorSubscriber();

        $definition = $this->getDefinition();

        $command = new DeleteCommand(
            $definition,
            ['id' => $this->ids->getBytes('product-1')],
            new EntityExistence(
                ProductDefinition::ENTITY_NAME,
                ['id' => $this->ids->get('product-1')],
                true,
                false,
                false,
                []
            ),
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext($context),
            [$command],
        );

        $subscriber->__invoke($event);

        static::assertFalse($command->hasField('available_stock'));
    }

    public function testThatAvailableStockIsCopiedOnInsert(): void
    {
        $context = Context::createDefaultContext();

        $subscriber = new AvailableStockMirrorSubscriber();

        $definition = $this->getDefinition();

        $command = new InsertCommand(
            $definition,
            ['stock' => 10],
            ['id' => $this->ids->getBytes('product-1')],
            new EntityExistence(
                ProductDefinition::ENTITY_NAME,
                ['id' => $this->ids->get('product-1')],
                false,
                false,
                false,
                []
            ),
            '/0'
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext($context),
            [$command],
        );

        $subscriber->__invoke($event);

        static::assertTrue($command->hasField('available_stock'));
        static::assertSame(10, $command->getPayload()['available_stock']);
    }

    public function testThatAvailableStockIsCopiedOnUpdate(): void
    {
        $context = Context::createDefaultContext();

        $subscriber = new AvailableStockMirrorSubscriber();

        $definition = $this->getDefinition();

        $command = new UpdateCommand(
            $definition,
            ['stock' => 10],
            ['id' => $this->ids->getBytes('product-1')],
            new EntityExistence(
                ProductDefinition::ENTITY_NAME,
                ['id' => $this->ids->get('product-1')],
                true,
                false,
                false,
                []
            ),
            '/0'
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext($context),
            [$command],
        );

        $subscriber->__invoke($event);

        static::assertTrue($command->hasField('available_stock'));
        static::assertSame(10, $command->getPayload()['available_stock']);
    }

    public function testThatAvailableStockIsNotCopiedOnUpdateIfNotInPayload(): void
    {
        $context = Context::createDefaultContext();

        $subscriber = new AvailableStockMirrorSubscriber();

        $definition = $this->getDefinition();

        $command = new UpdateCommand(
            $definition,
            [],
            ['id' => $this->ids->getBytes('product-1')],
            new EntityExistence(
                ProductDefinition::ENTITY_NAME,
                ['id' => $this->ids->get('product-1')],
                true,
                false,
                false,
                []
            ),
            '/0'
        );

        $event = EntityWriteEvent::create(
            WriteContext::createFromContext($context),
            [$command],
        );

        $subscriber->__invoke($event);

        static::assertFalse($command->hasField('available_stock'));
    }
}
