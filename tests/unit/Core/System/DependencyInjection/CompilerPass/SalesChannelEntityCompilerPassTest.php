<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\DependencyInjection\CompilerPass;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\BulkEntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityWriteGateway;
use Cicada\Core\Framework\DataAbstractionLayer\EntityExtension;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\System\DependencyInjection\CompilerPass\SalesChannelEntityCompilerPass;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInstanceRegistry;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(SalesChannelEntityCompilerPass::class)]
class SalesChannelEntityCompilerPassTest extends TestCase
{
    public function testExtensionsGetsAdded(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new Definition(ProductEntityExtension::class);
        $extension->setPublic(true);
        $extension->addTag('cicada.entity.extension');
        $container->setDefinition(ProductEntityExtension::class, $extension);

        $container->compile();

        $definition = $container->get(ProductDefinition::class);
        $definition->compile(new StaticDefinitionInstanceRegistry([], $this->createMock(ValidatorInterface::class), $this->createMock(EntityWriteGateway::class)));

        static::assertTrue($definition->getFields()->has('test'));
        static::assertInstanceOf(StringField::class, $definition->getFields()->get('test'));
    }

    public function testBulky(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new Definition(BulkyProductExtension::class);
        $extension->setPublic(true);
        $extension->addTag('cicada.bulk.entity.extension');
        $container->setDefinition(BulkyProductExtension::class, $extension);

        $container->compile();

        $definition = $container->get(ProductDefinition::class);
        $definition->compile(new StaticDefinitionInstanceRegistry([], $this->createMock(ValidatorInterface::class), $this->createMock(EntityWriteGateway::class)));

        static::assertTrue($definition->getFields()->has('test'));
        static::assertInstanceOf(StringField::class, $definition->getFields()->get('test'));
    }

    public function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new SalesChannelEntityCompilerPass());
        $definition = new Definition(SalesChannelDefinitionInstanceRegistry::class);
        $definition->setArguments([[], [], [], []]);

        $container->setDefinition(SalesChannelDefinitionInstanceRegistry::class, $definition);

        $productRegular = new Definition(ProductDefinition::class);
        $productRegular->setPublic(true);
        $productRegular->addTag('cicada.entity.definition');
        $container->setDefinition(ProductDefinition::class, $productRegular);

        return $container;
    }
}

/**
 * @internal
 */
class ProductEntityExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new StringField('test', 'test'))->addFlags(new Runtime())
        );
    }

    public function getEntityName(): string
    {
        return 'product';
    }
}

/**
 * @internal
 */
class BulkyProductExtension extends BulkEntityExtension
{
    public function collect(): \Generator
    {
        yield 'product' => [
            (new StringField('test', 'test'))->addFlags(new Runtime()),
        ];
    }
}
