<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\Serializer;

use Cicada\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Rule\RuleDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\ApiException;
use Cicada\Core\Framework\Api\Exception\UnsupportedEncoderInputException;
use Cicada\Core\Framework\Api\Serializer\JsonApiEncoder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\SerializationFixture;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicStruct;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicWithExtension;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicWithToManyExtension;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicWithToManyRelationships;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestBasicWithToOneRelationship;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestCollectionWithSelfReference;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestCollectionWithToOneRelationship;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestInternalFieldsAreFiltered;
use Cicada\Core\Framework\Test\Api\Serializer\fixtures\TestMainResourceShouldNotBeInIncluded;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\AssociationExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\CustomFieldPlainTestDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ExtendableDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ExtendedDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ExtendedProductDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ProductExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ScalarRuntimeExtension;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\User\UserDefinition;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class JsonApiEncoderTest extends TestCase
{
    use DataAbstractionLayerFieldTestBehaviour {
        tearDown as protected tearDownDefinitions;
    }
    use IntegrationTestBehaviour;

    private Connection $connection;

    private EntityRepository $productRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = static::getContainer()->get(Connection::class);

        $this->registerDefinition(ExtendedProductDefinition::class);
        $this->registerDefinitionWithExtensions(
            ProductDefinition::class,
            ProductExtension::class
        );

        $this->productRepository = static::getContainer()->get('product.repository');

        $this->connection->rollBack();

        $this->connection->executeStatement('
            DROP TABLE IF EXISTS `extended_product`;
            CREATE TABLE `extended_product` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NULL,
                `product_id` BINARY(16) NULL,
                `product_version_id` BINARY(16) NOT NULL DEFAULT 0x0fa91ce3e96a4bc2be4bd9ce752c3425,
                `language_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.extended_product.id` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`),
                CONSTRAINT `fk.extended_product.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->tearDownDefinitions();
        $this->connection->rollBack();

        $this->connection->executeStatement('DROP TABLE `extended_product`');
        $this->connection->beginTransaction();

        parent::tearDown();
    }

    /**
     * @return list<list<mixed>>
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

        $encoder = static::getContainer()->get(JsonApiEncoder::class);
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
        $encoder = static::getContainer()->get(JsonApiEncoder::class);
        $actual = $encoder->encode(new Criteria(), $definition, $fixture->getInput(), SerializationFixture::API_BASE_URL);
        $actual = json_decode((string) $actual, true, 512, \JSON_THROW_ON_ERROR);

        // remove extensions from test
        $actual = $this->arrayRemove($actual, 'extensions');
        $actual['included'] = $this->removeIncludedExtensions($actual['included']);

        $this->assertValues($fixture->getAdminJsonApiFixtures(), $actual);
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

        $encoder = static::getContainer()->get(JsonApiEncoder::class);
        $actual = $encoder->encode(new Criteria(), $extendableDefinition, $fixture->getInput(), SerializationFixture::API_BASE_URL);

        // check that empty "links" object is an object and not array: https://jsonapi.org/format/#document-links
        static::assertStringNotContainsString('"links":[]', $actual);
        static::assertStringContainsString('"links":{}', $actual);

        $this->assertValues($fixture->getAdminJsonApiFixtures(), json_decode((string) $actual, true, 512, \JSON_THROW_ON_ERROR));
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
        $fixture = new TestBasicWithToManyExtension();

        $encoder = static::getContainer()->get(JsonApiEncoder::class);
        $actual = $encoder->encode(new Criteria(), $extendableDefinition, $fixture->getInput(), SerializationFixture::API_BASE_URL);

        // check that empty "links" object is an object and not array: https://jsonapi.org/format/#document-links
        static::assertStringNotContainsString('"links":[]', $actual);
        static::assertStringContainsString('"links":{}', $actual);

        // check that empty "attributes" object is an object and not array: https://jsonapi.org/format/#document-resource-object-attributes
        static::assertStringNotContainsString('"attributes":[]', $actual);
        static::assertStringContainsString('"attributes":{}', $actual);

        $this->assertValues($fixture->getAdminJsonApiFixtures(), json_decode((string) $actual, true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testEncodeEntityWithToOneEntityExtension(): void
    {
        $productId = Uuid::randomHex();

        $this->productRepository->create([
            [
                'id' => $productId,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'name' => 'Test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 8.10, 'linked' => false]],
                'tax' => ['name' => 'test', 'taxRate' => 5],
                'manufacturer' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'cicada AG',
                    'link' => 'https://xchanming.com',
                ],
                'toOne' => [
                    'name' => 'test',
                ],
            ],
        ], Context::createDefaultContext());

        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('toOne');

        $productDefinition = static::getContainer()->get(ProductDefinition::class);

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())->get($productId);
        $encoder = static::getContainer()->get(JsonApiEncoder::class);
        $encodedResponse = $encoder->encode(new Criteria(), $productDefinition, $product, SerializationFixture::API_BASE_URL);
        $actual = json_decode((string) $encodedResponse, true, 512, \JSON_THROW_ON_ERROR);

        foreach ($actual['included'] as $included) {
            if ($included['type'] !== 'extension') {
                continue;
            }
            static::assertNotEmpty($included['relationships']['toOne']['data'], 'The relationship data to the loaded extension association is missing');
            static::assertEquals('extended_product', $included['relationships']['toOne']['data']['type']);
            static::assertNotEmpty($included['relationships']['toOne']['data']['id']);
        }
    }

    public function testEncodeEntityWithToManyEntityExtension(): void
    {
        $productId = Uuid::randomHex();

        $this->productRepository->create([
            [
                'id' => $productId,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'name' => 'Test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 8.10, 'linked' => false]],
                'tax' => ['name' => 'test', 'taxRate' => 5],
                'manufacturer' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'cicada AG',
                    'link' => 'https://xchanming.com',
                ],
                'oneToMany' => [
                    [
                        'name' => 'toMany01',
                    ],
                    [
                        'name' => 'toMany02',
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('oneToMany');

        $productDefinition = static::getContainer()->get(ProductDefinition::class);

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())->get($productId);
        $encoder = static::getContainer()->get(JsonApiEncoder::class);
        $encodedResponse = $encoder->encode(new Criteria(), $productDefinition, $product, SerializationFixture::API_BASE_URL);
        $actual = json_decode((string) $encodedResponse, true, 512, \JSON_THROW_ON_ERROR);

        foreach ($actual['included'] as $included) {
            if ($included['type'] !== 'extension') {
                continue;
            }
            static::assertNotEmpty($included['relationships']['oneToMany']['data'], 'The relationship data to the loaded extension association is missing');
            static::assertCount(2, $included['relationships']['oneToMany']['data']);
            static::assertEquals('extended_product', $included['relationships']['oneToMany']['data'][0]['type']);
            static::assertNotEmpty($included['relationships']['oneToMany']['data'][0]['id']);
        }
    }

    /**
     * @param array<mixed> $input
     * @param array<mixed>|null $output
     */
    #[DataProvider('customFieldsProvider')]
    public function testCustomFields(array $input, ?array $output): void
    {
        $encoder = static::getContainer()->get(JsonApiEncoder::class);

        $definition = new CustomFieldPlainTestDefinition();
        $definition->compile(static::getContainer()->get(DefinitionInstanceRegistry::class));
        $struct = new class extends Entity {
            use EntityCustomFieldsTrait;
        };
        $struct->setUniqueIdentifier(Uuid::randomHex());
        $struct->assign($input);

        $actual = json_decode((string) $encoder->encode(new Criteria(), $definition, $struct, SerializationFixture::API_BASE_URL), true, 512, \JSON_THROW_ON_ERROR);

        static::assertEquals($output, $actual['data']['attributes']['customFields']);
    }

    public static function customFieldsProvider(): \Generator
    {
        yield 'Custom field null' => [
            [
                'customFields' => null,
            ],
            null,
        ];

        yield 'Custom field with empty array' => [
            [
                'customFields' => [],
            ],
            [],
        ];

        yield 'Custom field with values' => [
            [
                'customFields' => ['bla'],
            ],
            ['bla'],
        ];
    }

    /**
     * @param array<mixed> $haystack
     *
     * @return array<mixed>
     */
    private function arrayRemove(array $haystack, string $keyToRemove): array
    {
        foreach ($haystack as $key => $value) {
            if (\is_array($value)) {
                $haystack[$key] = $this->arrayRemove($value, $keyToRemove);
            }

            if ($key === $keyToRemove) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }

    /**
     * @param array<array<mixed>> $array
     *
     * @return array<array<mixed>>
     */
    private function removeIncludedExtensions(array $array): array
    {
        $filtered = [];
        foreach ($array as $item) {
            if ($item['type'] !== 'extension') {
                $filtered[] = $item;
            }
        }

        return $filtered;
    }

    /**
     * @param array<mixed> $expected
     * @param array<mixed> $actual
     */
    private function assertValues(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            static::assertArrayHasKey($key, $actual);

            if (\is_array($value)) {
                $this->assertValues($value, $actual[$key]);
            } else {
                static::assertEquals($value, $actual[$key], 'Key: ' . $key);
            }
        }
    }
}
