<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\Serializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Framework\Api\ApiException;
use Cicada\Core\Framework\Api\Exception\UnsupportedEncoderInputException;
use Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\Api\Serializer\AssertValuesTrait;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\SerializationFixture;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicStruct;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicWithExtension;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicWithToManyRelationships;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicWithToOneRelationship;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestCollectionWithSelfReference;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestCollectionWithToOneRelationship;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestInternalFieldsAreFiltered;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestMainResourceShouldNotBeInIncluded;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\AssociationExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\CustomFieldTestDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ExtendableDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ExtendedDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ScalarRuntimeExtension;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\System\User\UserDefinition;

/**
 * @internal
 */
class JsonEntityEncoderTest extends TestCase
{
    use AssertValuesTrait;
    use DataAbstractionLayerFieldTestBehaviour;
    use KernelTestBehaviour;

    /**
     * @return array<array<mixed>>
     */
    public static function emptyInputProvider(): array
    {
        return [
            [null],
            ['string'],
            [1],
            [false],
            [new \DateTime()],
            [1.1],
        ];
    }

    #[DataProvider('emptyInputProvider')]
    public function testEncodeWithEmptyInput(mixed $input): void
    {
        if (Feature::isActive('v6.7.0.0')) {
            $this->expectException(ApiException::class);
        } else {
            $this->expectException(UnsupportedEncoderInputException::class);
        }
        $this->expectExceptionMessage('Unsupported encoder data provided. Only entities and entity collections are supported');

        $encoder = static::getContainer()->get(JsonEntityEncoder::class);
        $encoder->encode(new Criteria(), static::getContainer()->get(ProductDefinition::class), $input, SerializationFixture::API_BASE_URL);
    }

    /**
     * @return list<array{class-string<EntityDefinition>, SerializationFixture}>
     */
    public static function complexStructsProvider(): array
    {
        return [
            [MediaDefinition::class, new TestBasicStruct()],
            [UserDefinition::class, new TestBasicWithToManyRelationships()],
            [MediaDefinition::class, new TestBasicWithToOneRelationship()],
            [MediaFolderDefinition::class, new TestCollectionWithSelfReference()],
            [MediaDefinition::class, new TestCollectionWithToOneRelationship()],
            [RuleDefinition::class, new TestInternalFieldsAreFiltered()],
            [UserDefinition::class, new TestMainResourceShouldNotBeInIncluded()],
        ];
    }

    /**
     * @param class-string<EntityDefinition> $definitionClass
     */
    #[DataProvider('complexStructsProvider')]
    public function testEncodeComplexStructs(string $definitionClass, SerializationFixture $fixture): void
    {
        $definition = static::getContainer()->get($definitionClass);
        static::assertInstanceOf(EntityDefinition::class, $definition);
        $encoder = static::getContainer()->get(JsonEntityEncoder::class);
        $actual = $encoder->encode(new Criteria(), $definition, $fixture->getInput(), SerializationFixture::API_BASE_URL);

        $this->assertValues($fixture->getAdminJsonFixtures(), $actual);
    }

    /**
     * Not possible with data provider as we have to manipulate the container, but the data provider run before all tests
     */
    public function testEncodeStructWithExtension(): void
    {
        $this->registerDefinition(ExtendableDefinition::class, ExtendedDefinition::class);
        $extendableDefinition = new ExtendableDefinition();
        $extendableDefinition->addExtension(new AssociationExtension());
        $extendableDefinition->addExtension(new ScalarRuntimeExtension());

        $extendableDefinition->compile(static::getContainer()->get(DefinitionInstanceRegistry::class));
        $fixture = new TestBasicWithExtension();

        $encoder = static::getContainer()->get(JsonEntityEncoder::class);
        $actual = $encoder->encode(new Criteria(), $extendableDefinition, $fixture->getInput(), SerializationFixture::API_BASE_URL);

        unset($actual['apiAlias']);
        static::assertEquals($fixture->getAdminJsonFixtures(), $actual);
        $this->assertValues($fixture->getAdminJsonFixtures(), $actual);
    }

    /**
     * Not possible with data provider as we have to manipulate the container, but the data provider run before all tests
     */
    public function testEncodeStructWithToManyExtension(): void
    {
        $this->registerDefinition(ExtendableDefinition::class, ExtendedDefinition::class);
        $extendableDefinition = new ExtendableDefinition();
        $extendableDefinition->addExtension(new AssociationExtension());

        $extendableDefinition->compile(static::getContainer()->get(DefinitionInstanceRegistry::class));
        $fixture = new TestBasicWithExtension();

        $encoder = static::getContainer()->get(JsonEntityEncoder::class);
        $actual = $encoder->encode(new Criteria(), $extendableDefinition, $fixture->getInput(), SerializationFixture::API_BASE_URL);

        unset($actual['apiAlias']);
        static::assertEquals($fixture->getAdminJsonFixtures(), $actual);
    }

    /**
     * @param array{customFields: mixed}|array{translated: array{customFields: mixed}} $input
     * @param array{customFields: mixed}|array{translated: array{customFields: mixed}} $output
     */
    #[DataProvider('customFieldsProvider')]
    public function testCustomFields(array $input, array $output): void
    {
        $encoder = static::getContainer()->get(JsonEntityEncoder::class);

        $definition = new CustomFieldTestDefinition();
        $definition->compile(static::getContainer()->get(DefinitionInstanceRegistry::class));
        $struct = new class extends Entity {
            use EntityCustomFieldsTrait;
        };
        $struct->assign($input);

        $actual = $encoder->encode(new Criteria(), $definition, $struct, SerializationFixture::API_BASE_URL);

        static::assertEquals($output, array_intersect_key($output, $actual));
    }

    /**
     * @return \Generator<string, array{0: array{customFields: mixed}, 1: array{customFields: mixed}}|array{0: array{translated: array{customFields: mixed}}, 1: array{translated: array{customFields: mixed}}}>
     */
    public static function customFieldsProvider(): \Generator
    {
        yield 'Custom field null' => [
            [
                'customFields' => null,
            ],
            [
                'customFields' => null,
            ],
        ];

        yield 'Custom field with empty array' => [
            [
                'customFields' => [],
            ],
            [
                'customFields' => new \stdClass(),
            ],
        ];

        yield 'Custom field with values' => [
            [
                'customFields' => ['bla'],
            ],
            [
                'customFields' => ['bla'],
            ],
        ];

        // translated

        yield 'Custom field translated null' => [
            [
                'translated' => [
                    'customFields' => null,
                ],
            ],
            [
                'translated' => [
                    'customFields' => null,
                ],
            ],
        ];

        yield 'Custom field translated with empty array' => [
            [
                'translated' => [
                    'customFields' => [],
                ],
            ],
            [
                'translated' => [
                    'customFields' => new \stdClass(),
                ],
            ],
        ];

        yield 'Custom field translated with values' => [
            [
                'translated' => [
                    'customFields' => ['bla'],
                ],
            ],
            [
                'translated' => [
                    'customFields' => ['bla'],
                ],
            ],
        ];
    }
}
