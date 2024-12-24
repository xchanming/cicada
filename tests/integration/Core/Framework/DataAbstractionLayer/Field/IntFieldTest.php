<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\IntFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class IntFieldTest extends TestCase
{
    use KernelTestBehaviour;

    public function testIntFieldSerializerNullValue(): void
    {
        $serializer = static::getContainer()->get(IntFieldSerializer::class);

        $data = new KeyValuePair('count', null, false);

        $this->expectException(WriteConstraintViolationException::class);

        try {
            $serializer->encode(
                $this->getIntField(),
                EntityExistence::createEmpty(),
                $data,
                $this->getWriteParameterBagMock()
            )->current();
        } catch (WriteConstraintViolationException $e) {
            static::assertSame('/count', $e->getViolations()->get(0)->getPropertyPath());
            /* Unexpected language has to be fixed NEXT-9419 */
            // static::assertSame('This value should not be blank.', $e->getViolations()->get(0)->getMessage());

            throw $e;
        }
    }

    public function testIntFieldSerializerWrongValueType(): void
    {
        $serializer = static::getContainer()->get(IntFieldSerializer::class);

        $data = new KeyValuePair('count', 'foo', false);

        $this->expectException(WriteConstraintViolationException::class);

        try {
            $serializer->encode(
                $this->getIntField(),
                EntityExistence::createEmpty(),
                $data,
                $this->getWriteParameterBagMock()
            )->current();
        } catch (WriteConstraintViolationException $e) {
            static::assertSame('/count', $e->getViolations()->get(0)->getPropertyPath());
            /* Unexpected language has to be fixed NEXT-9419 */
            // static::assertSame('This value should be of type int.', $e->getViolations()->get(0)->getMessage());

            throw $e;
        }
    }

    public function testIntFieldSerializerZeroValue(): void
    {
        $serializer = static::getContainer()->get(IntFieldSerializer::class);

        $data = new KeyValuePair('count', 0, false);

        $field = $this->getIntField();

        static::assertSame(
            0,
            $serializer->encode(
                $field,
                EntityExistence::createEmpty(),
                $data,
                $this->getWriteParameterBagMock()
            )->current()
        );
    }

    public function testIntFieldSerializerIntValue(): void
    {
        $serializer = static::getContainer()->get(IntFieldSerializer::class);

        $data = new KeyValuePair('count', 15, false);

        static::assertSame(
            15,
            $serializer->encode(
                $this->getIntField(),
                EntityExistence::createEmpty(),
                $data,
                $this->getWriteParameterBagMock()
            )->current()
        );
    }

    public function testIntFieldSerializerNotRequiredValue(): void
    {
        $serializer = static::getContainer()->get(IntFieldSerializer::class);

        $data = new KeyValuePair('count', null, false);

        static::assertNull(
            $serializer->encode(
                $this->getIntField(false),
                EntityExistence::createEmpty(),
                $data,
                $this->getWriteParameterBagMock()
            )->current()
        );
    }

    private function getWriteParameterBagMock(): WriteParameterBag
    {
        $mockBuilder = $this->getMockBuilder(WriteParameterBag::class);
        $mockBuilder->disableOriginalConstructor();

        return $mockBuilder->getMock();
    }

    private function getIntField(bool $required = true): Field
    {
        $field = new IntField('count', 'count');

        return $required ? $field->addFlags(new ApiAware(), new Required()) : $field;
    }
}
