<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\DataAbstractionLayer\FieldSerializer;

use Cicada\Core\Content\Flow\Aggregate\FlowTemplate\FlowTemplateDefinition;
use Cicada\Core\Content\Flow\DataAbstractionLayer\Field\FlowTemplateConfigField;
use Cicada\Core\Content\Flow\DataAbstractionLayer\FieldSerializer\FlowTemplateConfigFieldSerializer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(FlowTemplateConfigFieldSerializer::class)]
class FlowTemplateConfigFieldSerializerTest extends TestCase
{
    private FlowTemplateConfigFieldSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $definitionRegistry = $this->createMock(DefinitionInstanceRegistry::class);

        $this->serializer = new FlowTemplateConfigFieldSerializer($validator, $definitionRegistry);
    }

    public function testSerializeWithInvalidConfigArray(): void
    {
        static::expectException(WriteConstraintViolationException::class);

        $this->encode([
            'eventName' => 111,
            'description' => 'description test',
            'sequences' => [],
        ]);
    }

    public function testSerializeWithValidConfigArray(): void
    {
        $config = $this->encode([
            'eventName' => 'test',
            'description' => 'description test',
            'sequences' => [
                [
                    'id' => '1111',
                    'actionName' => 'action.name',
                ],
            ],
        ]);

        if ($config === null) {
            return;
        }

        $data = json_decode($config, true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('sequences', $data);
        static::assertCount(1, $data['sequences']);

        static::assertArrayHasKey('parentId', $data['sequences'][0]);
        static::assertArrayHasKey('ruleId', $data['sequences'][0]);
        static::assertArrayHasKey('position', $data['sequences'][0]);
        static::assertArrayHasKey('displayGroup', $data['sequences'][0]);
        static::assertArrayHasKey('trueCase', $data['sequences'][0]);

        static::assertEquals(1, $data['sequences'][0]['position']);
        static::assertEquals(1, $data['sequences'][0]['displayGroup']);
        static::assertEquals(0, $data['sequences'][0]['trueCase']);
    }

    public function testFieldArgumentNotInstanceOfFlowTemplateConfigField(): void
    {
        static::expectException(DataAbstractionLayerException::class);

        $this->encode([
            'eventName' => 111,
            'description' => 'description test',
            'sequences' => [],
        ], new DateField('config', 'config'));
    }

    public function testDataValueIsNotArray(): void
    {
        $config = $this->encode();
        static::assertNull($config);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encode(?array $data = null, ?Field $field = null): ?string
    {
        $field ??= new FlowTemplateConfigField('config', 'config');
        $existence = new EntityExistence('config', ['someId' => true], true, false, false, []);
        $keyPair = new KeyValuePair('someId', $data, false);
        $bag = new WriteParameterBag(
            $this->createMock(FlowTemplateDefinition::class),
            WriteContext::createFromContext(Context::createDefaultContext()),
            '',
            new WriteCommandQueue()
        );

        $data = iterator_to_array($this->serializer->encode($field, $existence, $keyPair, $bag));

        return $data['config'] ?? null;
    }
}
