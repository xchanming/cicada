<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel\Entity;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInstanceRegistry;
use Cicada\Core\System\SalesChannel\Exception\SalesChannelRepositoryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(SalesChannelDefinitionInstanceRegistry::class)]
class SalesChannelDefinitionInstanceRegistryTest extends TestCase
{
    public function testRegister(): void
    {
        $registry = new SalesChannelDefinitionInstanceRegistry(
            'sales_channel_definition.',
            new Container(),
            [],
            []
        );

        $registry->register(new ProductDefinition());

        static::assertInstanceOf(ProductDefinition::class, $registry->get(ProductDefinition::class));
        static::assertTrue($registry->has(ProductDefinition::ENTITY_NAME));
        static::assertInstanceOf(ProductDefinition::class, $registry->getByEntityName(ProductDefinition::ENTITY_NAME));
        static::assertInstanceOf(ProductDefinition::class, $registry->getByEntityClass(new ProductEntity()));
    }

    public function testItThrowsExceptionWhenSalesChannelRepositoryWasNotFoundByEntityName(): void
    {
        $registry = new SalesChannelDefinitionInstanceRegistry(
            'sales_channel_definition.',
            new Container(),
            [],
            []
        );

        $this->expectException(SalesChannelRepositoryNotFoundException::class);
        $registry->getSalesChannelRepository('fooBar');
    }
}
