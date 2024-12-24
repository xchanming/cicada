<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Write\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\ParentRelationValidator;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Cicada\Core\System\Tax\TaxDefinition;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(ParentRelationValidator::class)]
class ParentRelationValidatorTest extends TestCase
{
    private StaticDefinitionInstanceRegistry $registry;

    private ParentRelationValidator $validator;

    protected function setUp(): void
    {
        $this->registry = new StaticDefinitionInstanceRegistry(
            [ProductDefinition::class, TaxDefinition::class],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );
        $this->validator = new ParentRelationValidator($this->registry);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = ParentRelationValidator::getSubscribedEvents();
        static::assertCount(1, $events);
        static::assertSame('preValidate', $events[PreWriteValidationEvent::class]);
    }

    public function testPreValidateIgnoresNotParentAware(): void
    {
        $id = Uuid::randomBytes();
        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new InsertCommand($this->registry->getByEntityName('tax'), ['id' => $id, 'parent_id' => $id], ['id' => $id], $this->createMock(EntityExistence::class), '/insert'),
                new UpdateCommand($this->registry->getByEntityName('tax'), ['id' => $id, 'parent_id' => $id], ['id' => $id], $this->createMock(EntityExistence::class), '/update'),
            ]
        );

        $this->validator->preValidate($event);

        static::assertCount(0, $event->getExceptions()->getExceptions());
    }

    public function testPreValidateCatchesInsert(): void
    {
        $id = Uuid::randomBytes();
        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new InsertCommand($this->registry->getByEntityName('product'), ['id' => $id, 'parent_id' => $id], ['id' => $id], $this->createMock(EntityExistence::class), '/insert'),
            ]
        );

        $this->validator->preValidate($event);

        static::assertCount(1, $event->getExceptions()->getExceptions());
        $exception = $event->getExceptions()->getExceptions()[0];
        static::assertInstanceOf(WriteConstraintViolationException::class, $exception);
        static::assertCount(1, $exception->getViolations());
        $violation = $exception->getViolations()->get(0);
        static::assertSame(ParentRelationValidator::VIOLATION_PARENT_RELATION_DOES_NOT_ALLOW_SELF_REFERENCES, $violation->getCode());
        static::assertSame(
            \sprintf('The product entity with id "%s" can not reference to itself as parent.', Uuid::fromBytesToHex($id)),
            $violation->getMessage()
        );
        static::assertSame('/insert/parentId', $violation->getPropertyPath());
    }

    public function testPreValidateCatchesUpdate(): void
    {
        $id = Uuid::randomBytes();
        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new UpdateCommand($this->registry->getByEntityName('product'), ['id' => $id, 'parent_id' => $id], ['id' => $id], $this->createMock(EntityExistence::class), '/update'),
            ]
        );

        $this->validator->preValidate($event);

        static::assertCount(1, $event->getExceptions()->getExceptions());
        $exception = $event->getExceptions()->getExceptions()[0];
        static::assertInstanceOf(WriteConstraintViolationException::class, $exception);
        static::assertCount(1, $exception->getViolations());
        $violation = $exception->getViolations()->get(0);
        static::assertSame(ParentRelationValidator::VIOLATION_PARENT_RELATION_DOES_NOT_ALLOW_SELF_REFERENCES, $violation->getCode());
        static::assertSame(
            \sprintf('The product entity with id "%s" can not reference to itself as parent.', Uuid::fromBytesToHex($id)),
            $violation->getMessage()
        );
        static::assertSame('/update/parentId', $violation->getPropertyPath());
    }

    public function testPreValidateAllowsNonSelfReferences(): void
    {
        $id = Uuid::randomBytes();
        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new UpdateCommand($this->registry->getByEntityName('product'), ['id' => $id, 'parent_id' => Uuid::randomBytes()], ['id' => $id], $this->createMock(EntityExistence::class), '/insert'),
            ]
        );

        $this->validator->preValidate($event);

        static::assertCount(0, $event->getExceptions()->getExceptions());
    }
}
