<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\ProductFeatureSet;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Test\Product\ProductFeatureSet\ProductFeatureSetFixtures;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProductFeatureSetPropertyTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ProductFeatureSetFixtures;

    #[TestWith(['featureSet'])]
    public function testDefinitionFieldsAreComplete(string $field): void
    {
        $definition = static::getContainer()->get(ProductDefinition::class);

        static::assertTrue($definition->getFields()->has($field));
    }

    #[TestWith(['getFeatureSet'])]
    public function testEntityIsComplete(string $method): void
    {
        static::assertTrue(method_exists(ProductEntity::class, $method));
    }

    #[TestWith(['FeatureSetBasic'])]
    #[TestWith(['FeatureSetComplete'])]
    public function testFeatureSetsCanBeCreated(string $type): void
    {
        $this->getFeatureSetFixture($type);
    }
}
