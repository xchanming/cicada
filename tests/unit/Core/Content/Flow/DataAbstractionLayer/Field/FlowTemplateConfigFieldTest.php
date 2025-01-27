<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\DataAbstractionLayer\Field;

use Cicada\Core\Content\Flow\DataAbstractionLayer\Field\FlowTemplateConfigField;
use Cicada\Core\Content\Flow\DataAbstractionLayer\FieldSerializer\FlowTemplateConfigFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(FlowTemplateConfigField::class)]
class FlowTemplateConfigFieldTest extends TestCase
{
    private FlowTemplateConfigField $field;

    protected function setUp(): void
    {
        $this->field = new FlowTemplateConfigField('config', 'config');
    }

    public function testGetSerializerWillReturnFieldSerializerInterfaceInstance(): void
    {
        $registry = $this->createMock(DefinitionInstanceRegistry::class);
        $registry
            ->method('getSerializer')
            ->willReturn(
                new FlowTemplateConfigFieldSerializer(
                    $this->createMock(ValidatorInterface::class),
                    $registry
                )
            );

        $this->field->compile($registry);

        static::assertInstanceOf(FlowTemplateConfigFieldSerializer::class, $this->field->getSerializer());
    }
}
