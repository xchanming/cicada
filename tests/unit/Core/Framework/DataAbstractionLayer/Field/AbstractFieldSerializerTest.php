<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\AbstractFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(AbstractFieldSerializer::class)]
class AbstractFieldSerializerTest extends TestCase
{
    public function testGetConstraintsOnlyCalledOnce(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());
        $serializer = new TestFieldSerializer(
            $validator,
            $this->createMock(DefinitionInstanceRegistry::class)
        );

        static::assertSame(0, $serializer->getConstraintsCallCounter);
        $entityExistence = new EntityExistence('test', ['id' => Uuid::randomHex()], true, false, false, []);
        $field = new StringField('test', 'test');

        $data = new KeyValuePair('foo', 'bar', true);

        static::assertNotNull($serializer->encode($field, $entityExistence, $data, $this->createMock(WriteParameterBag::class))->current());
        static::assertSame(1, $serializer->getConstraintsCallCounter);

        static::assertNotNull($serializer->encode($field, $entityExistence, $data, $this->createMock(WriteParameterBag::class))->current());
        static::assertSame(1, $serializer->getConstraintsCallCounter);
    }

    public function testCaching(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());
        $serializer = new TestFieldSerializer(
            $validator,
            $this->createMock(DefinitionInstanceRegistry::class)
        );
        $parameters = $this->createMock(WriteParameterBag::class);

        static::assertSame(0, $serializer->getConstraintsCallCounter);
        $entityExistence = new EntityExistence('test', ['id' => Uuid::randomHex()], true, false, false, []);

        $data = new KeyValuePair('foo', 'bar', true);
        $field = new StringField('test', 'test');
        static::assertNotNull($serializer->encode($field, $entityExistence, $data, $parameters)->current());
        static::assertSame(1, $serializer->getConstraintsCallCounter);

        $serializer->getConstraintsCallCounter = 0;
        $newField = new StringField('test', 'test');
        // a different field object should not return the cached constraints of the other field
        static::assertNotNull($serializer->encode($newField, $entityExistence, $data, $parameters)->current());
        static::assertSame(1, $serializer->getConstraintsCallCounter);
    }
}

/**
 * @internal
 */
class TestFieldSerializer extends AbstractFieldSerializer
{
    public int $getConstraintsCallCounter = 0;

    public function encode(Field $field, EntityExistence $existence, KeyValuePair $data, WriteParameterBag $parameters): \Generator
    {
        $this->validateIfNeeded($field, $existence, $data, $parameters);

        yield $data->getKey() => $data->getValue();
    }

    public function decode(Field $field, mixed $value): mixed
    {
        return $value;
    }

    protected function getConstraints(Field $field): array
    {
        ++$this->getConstraintsCallCounter;

        return [new NotBlank()];
    }
}
