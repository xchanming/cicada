<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToOneAssociationFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\FieldException\ExpectedArrayException;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteCommandExtractor;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(ManyToOneAssociationFieldSerializer::class)]
class ManyToOneAssociationFieldSerializerTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $payload
     */
    #[DataProvider('invalidArrayProvider')]
    public function testExceptionIsThrownIfDataIsNotAssociativeArray(array $payload): void
    {
        $this->expectException(DataAbstractionLayerException::class);
        static::expectExceptionMessage('Expected data at /customer to be an associative array.');

        new StaticDefinitionInstanceRegistry(
            [
                OrderDefinition::class => $orderDefinition = new OrderDefinition(),
                CustomerDefinition::class => new CustomerDefinition(),
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $field = $orderDefinition->getField('customer');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);

        $serializer = new ManyToOneAssociationFieldSerializer($this->createMock(WriteCommandExtractor::class));

        $params = new WriteParameterBag(
            $orderDefinition,
            WriteContext::createFromContext(Context::createDefaultContext()),
            '/customer',
            new WriteCommandQueue()
        );

        $result = $serializer->encode(
            $field,
            $this->createMock(EntityExistence::class),
            new KeyValuePair('customer', $payload, true),
            $params
        );

        iterator_to_array($result);
    }

    public function testExceptionInNormalizationIsThrownIfDataIsNotArray(): void
    {
        $this->expectException(ExpectedArrayException::class);
        static::expectExceptionMessage('Expected data at /0/customer to be an array.');

        new StaticDefinitionInstanceRegistry(
            [
                OrderDefinition::class => $orderDefinition = new OrderDefinition(),
                CustomerDefinition::class => new CustomerDefinition(),
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $field = $orderDefinition->getField('customer');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);

        $serializer = new ManyToOneAssociationFieldSerializer($this->createMock(WriteCommandExtractor::class));

        $params = new WriteParameterBag(
            $orderDefinition,
            WriteContext::createFromContext(Context::createDefaultContext()),
            '/0',
            new WriteCommandQueue()
        );

        $serializer->normalize(
            $field,
            ['customer' => 'foobar'],
            $params,
        );
    }

    public static function invalidArrayProvider(): \Generator
    {
        yield [
            'payload' => ['should-be-an-associative-array'],
        ];

        yield [
            'payload' => [1 => 'apple', 'orange'],
        ];

        yield [
            'payload' => [0 => 'apple', 1 => 'orange'],
        ];

        yield [
            'payload' => [3 => 'apple', 5 => 'orange'],
        ];
    }

    public function testCanEncodeAssociativeArray(): void
    {
        new StaticDefinitionInstanceRegistry(
            [
                OrderDefinition::class => $orderDefinition = new OrderDefinition(),
                CustomerDefinition::class => new CustomerDefinition(),
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $field = $orderDefinition->getField('customer');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);

        $serializer = new ManyToOneAssociationFieldSerializer($this->createMock(WriteCommandExtractor::class));

        $params = new WriteParameterBag(
            $orderDefinition,
            WriteContext::createFromContext(Context::createDefaultContext()),
            '/customer',
            new WriteCommandQueue()
        );

        $id = Uuid::randomHex();

        $result = $serializer->encode(
            $field,
            $this->createMock(EntityExistence::class),
            new KeyValuePair('customer', ['id' => $id, 'name' => 'Jimmy'], true),
            $params
        );

        static::assertEquals([], iterator_to_array($result));
    }
}

/**
 * @internal
 */
class OrderDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'order';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            new FkField('customer_id', 'customerId', CustomerDefinition::class),

            new ManyToOneAssociationField(
                'customer',
                'customer_id',
                CustomerDefinition::class,
                'id',
            ),
        ]);
    }
}

/**
 * @internal
 */
class CustomerDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'customer';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('first_name', 'first_name'))->addFlags(new Required()),
            (new StringField('last_name', 'last_name'))->addFlags(new Required()),

            new OneToManyAssociationField(
                'orders',
                OrderDefinition::class,
                'customer_id',
            ),
        ]);
    }
}
