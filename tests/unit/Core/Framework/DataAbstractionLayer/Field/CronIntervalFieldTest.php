<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CronIntervalField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CronIntervalFieldSerializer;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CronIntervalField::class)]
class CronIntervalFieldTest extends TestCase
{
    private CronIntervalField $field;

    protected function setUp(): void
    {
        $this->field = new CronIntervalField('name', 'name');
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
                new CronIntervalFieldSerializer(
                    $this->createMock(ValidatorInterface::class),
                    $registry
                )
            );
        $registry->method('getResolver');
        $registry->method('getAccessorBuilder');
        $this->field->compile($registry);

        static::assertInstanceOf(CronIntervalFieldSerializer::class, $this->field->getSerializer());
    }
}
