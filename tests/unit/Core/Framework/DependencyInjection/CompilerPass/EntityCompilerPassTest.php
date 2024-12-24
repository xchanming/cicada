<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DependencyInjection\CompilerPass;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DependencyInjection\CompilerPass\EntityCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
#[CoversClass(EntityCompilerPass::class)]
class EntityCompilerPassTest extends TestCase
{
    public function testEntityRepositoryAutowiring(): void
    {
        $container = new ContainerBuilder();

        $container->register(CustomerAddressDefinition::class, CustomerAddressDefinition::class)
            ->addTag('cicada.entity.definition');
        $container->register(CustomerDefinition::class, CustomerDefinition::class)
            ->addTag('cicada.entity.definition');

        $container->register(DefinitionInstanceRegistry::class, DefinitionInstanceRegistry::class)
            ->addArgument(new Reference('service_container'))
            ->addArgument([
                CustomerDefinition::ENTITY_NAME => CustomerDefinition::class,
                CustomerAddressDefinition::ENTITY_NAME => CustomerAddressDefinition::class,
            ])
            ->addArgument([
                CustomerDefinition::ENTITY_NAME => 'customer.repository',
                CustomerAddressDefinition::ENTITY_NAME => 'customer_address.repository',
            ]);

        $entityCompilerPass = new EntityCompilerPass();
        $entityCompilerPass->process($container);

        static::assertTrue($container->hasAlias('Cicada\Core\Framework\DataAbstractionLayer\EntityRepository $customerRepository'));
        static::assertTrue($container->hasAlias('Cicada\Core\Framework\DataAbstractionLayer\EntityRepository $customerAddressRepository'));
    }

    public function testEntityRepositoryAutowiringForAlreadyDefinedRepositories(): void
    {
        $container = new ContainerBuilder();

        $container
            ->register(ProductDefinition::class, ProductDefinition::class)
            ->addTag('cicada.entity.definition')
        ;

        $container
            ->register(DefinitionInstanceRegistry::class, DefinitionInstanceRegistry::class)
            ->addArgument(new Reference('service_container'))
            ->addArgument([
                ProductDefinition::ENTITY_NAME => ProductDefinition::class,
            ])
            ->addArgument([
                ProductDefinition::ENTITY_NAME => 'product.repository',
            ])
        ;

        $container
            ->register('product.repository', EntityRepository::class)
            ->addArgument(new Reference(ProductDefinition::class))
        ;

        $entityCompilerPass = new EntityCompilerPass();
        $entityCompilerPass->process($container);

        static::assertTrue($container->hasAlias('Cicada\Core\Framework\DataAbstractionLayer\EntityRepository $productRepository'));
    }
}
