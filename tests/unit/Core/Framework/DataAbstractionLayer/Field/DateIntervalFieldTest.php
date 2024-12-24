<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Field;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateIntervalField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\DateIntervalFieldSerializer;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(DateIntervalField::class)]
class DateIntervalFieldTest extends TestCase
{
    private DateIntervalField $field;

    protected function setUp(): void
    {
        $this->field = new DateIntervalField('name', 'name');
    }

    public function testGetStorageName(): void
    {
        static::assertSame('name', $this->field->getStorageName());
    }

    public function testGetSerializerWillReturnFieldSerializerInterfaceInstance(): void
    {
        $registry = $this->createMock(DefinitionInstanceRegistry::class);
        $registry
            ->method('getSerializer')
            ->willReturn(
                new DateIntervalFieldSerializer(
                    $this->createMock(ValidatorInterface::class),
                    $registry
                )
            );
        $registry->method('getResolver');
        $registry->method('getAccessorBuilder');
        $this->field->compile($registry);

        static::assertInstanceOf(DateIntervalFieldSerializer::class, $this->field->getSerializer());
    }
}
