<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\ListPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Version\CalculatedPriceFieldTestDefinition;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class CalculatedPriceFieldTest extends TestCase
{
    use DataAbstractionLayerFieldTestBehaviour;
    use IntegrationTestBehaviour;

    public function testListPrice(): void
    {
        $definition = $this->registerDefinition(CalculatedPriceFieldTestDefinition::class);
        $connection = static::getContainer()->get(Connection::class);

        $connection->rollBack();
        $connection->executeStatement(CalculatedPriceFieldTestDefinition::getCreateTable());
        $connection->beginTransaction();

        $ids = new IdsCollection();

        $data = [
            'id' => $ids->create('entity'),
            'price' => new CalculatedPrice(
                100,
                100,
                new CalculatedTaxCollection([]),
                new TaxRuleCollection([]),
                1,
                null,
                ListPrice::createFromUnitPrice(100, 200)
            ),
        ];

        $writeContext = WriteContext::createFromContext(Context::createDefaultContext());

        static::getContainer()->get(EntityWriter::class)
            ->insert($definition, [$data], $writeContext);

        $entity = static::getContainer()->get(EntityReaderInterface::class)
            ->read($definition, new Criteria([$ids->get('entity')]), Context::createDefaultContext())
            ->get($ids->get('entity'));
        static::assertInstanceOf(ArrayEntity::class, $entity);

        $price = $entity->get('price');
        static::assertInstanceOf(CalculatedPrice::class, $price);

        static::assertInstanceOf(ListPrice::class, $price->getListPrice());
        static::assertSame(200.0, $price->getListPrice()->getPrice());
        static::assertSame(-100.0, $price->getListPrice()->getDiscount());
        static::assertSame(50.0, $price->getListPrice()->getPercentage());
    }
}
