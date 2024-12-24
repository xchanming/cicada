<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\ProductFeatureSet;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Aggregate\ProductFeatureSetTranslation\ProductFeatureSetTranslationCollection;
use Cicada\Core\Content\Product\Aggregate\ProductFeatureSetTranslation\ProductFeatureSetTranslationDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductFeatureSetTranslation\ProductFeatureSetTranslationEntity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
#[Package('inventory')]
class ProductFeatureSetTranslationEntityTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('definitionMethodProvider')]
    public function testEntityDefinitionIsComplete(string $method, string $returnValue): void
    {
        $definition = static::getContainer()->get(ProductFeatureSetTranslationDefinition::class);

        static::assertTrue(method_exists($definition, $method));
        static::assertEquals($returnValue, $definition->$method()); /* @phpstan-ignore-line */
    }

    #[TestWith(['name'])]
    #[TestWith(['description'])]
    public function testDefinitionFieldsAreComplete(string $field): void
    {
        $definition = static::getContainer()->get(ProductFeatureSetTranslationDefinition::class);

        static::assertTrue($definition->getFields()->has($field));
    }

    #[TestWith(['getProductFeatureSetId'])]
    #[TestWith(['getName'])]
    #[TestWith(['getDescription'])]
    #[TestWith(['getProductFeatureSet'])]
    public function testEntityIsComplete(string $method): void
    {
        static::assertTrue(method_exists(ProductFeatureSetTranslationEntity::class, $method));
    }

    public function testRepositoryIsWorking(): void
    {
        static::assertInstanceOf(EntityRepository::class, static::getContainer()->get('product_feature_set_translation.repository'));
    }

    /**
     * @return list<array<string>>
     */
    public static function definitionMethodProvider(): array
    {
        return [
            [
                'getEntityName',
                'product_feature_set_translation',
            ],
            [
                'getCollectionClass',
                ProductFeatureSetTranslationCollection::class,
            ],
            [
                'getEntityClass',
                ProductFeatureSetTranslationEntity::class,
            ],
        ];
    }
}
