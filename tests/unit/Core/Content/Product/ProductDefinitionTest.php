<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityWriteGateway;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(ProductDefinition::class)]
class ProductDefinitionTest extends TestCase
{
    public function testSearchFields(): void
    {
        // don't change this list, each additional field will reduce the performance

        $registry = new StaticDefinitionInstanceRegistry(
            [ProductDefinition::class],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGateway::class)
        );

        $definition = $registry->getByEntityName('product');

        $fields = $definition->getFields();

        $searchable = $fields->filterByFlag(SearchRanking::class);

        $keys = $searchable->getKeys();

        // NEVER add an association to this list!!! otherwise, the API query takes too long and shops with many products (more than 1000) will fail
        $expected = ['customSearchKeywords', 'productNumber', 'manufacturerNumber', 'ean', 'name'];

        sort($expected);
        sort($keys);

        static::assertEquals($expected, $keys);
    }
}
