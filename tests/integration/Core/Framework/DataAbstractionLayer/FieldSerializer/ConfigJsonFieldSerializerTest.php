<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\FieldSerializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ConfigJsonField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ConfigJsonFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\JsonDefinition;
use Cicada\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
class ConfigJsonFieldSerializerTest extends TestCase
{
    use CacheTestBehaviour;
    use DataAbstractionLayerFieldTestBehaviour;
    use KernelTestBehaviour;

    private ConfigJsonFieldSerializer $serializer;

    private ConfigJsonField $field;

    private EntityExistence $existence;

    private WriteParameterBag $parameters;

    protected function setUp(): void
    {
        $this->serializer = static::getContainer()->get(ConfigJsonFieldSerializer::class);
        $this->field = new ConfigJsonField('data', 'data');
        $this->field->addFlags(new ApiAware(), new Required());

        $definition = $this->registerDefinition(JsonDefinition::class);
        $this->existence = new EntityExistence($definition->getEntityName(), [], false, false, false, []);

        $this->parameters = new WriteParameterBag(
            $definition,
            WriteContext::createFromContext(Context::createDefaultContext()),
            '',
            new WriteCommandQueue()
        );
    }

    /**
     * @return array<int, array<int, array<int, array<int|string, array<string, int>|int|string>|bool|float|int|string|null>>>
     */
    public static function serializerProvider(): array
    {
        return [
            [['string']],
            [[11234]],
            [[11234.123243]],
            [[
                [
                    'foo' => 'sadfsadf',
                    'bar' => [
                        'a' => 1234,
                    ],
                ],
            ]],
            [[
                [1, 2, 3],
            ]],
            [[null]],
            [[false]],
            [[0]],
            [['']],
        ];
    }

    /**
     * @param array<string, int>|int|string|bool|float|null $input
     */
    #[DataProvider('serializerProvider')]
    public function testSerializer($input): void
    {
        $kvPair = new KeyValuePair('password', $input, true);
        $encoded = $this->serializer->encode($this->field, $this->existence, $kvPair, $this->parameters)->current();
        $decoded = $this->serializer->decode($this->field, $encoded);

        static::assertEquals($input, $decoded, 'Output should be equal to the input');
    }
}
