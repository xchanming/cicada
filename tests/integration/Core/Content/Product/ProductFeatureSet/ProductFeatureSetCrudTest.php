<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\ProductFeatureSet;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProductFeatureSetCrudTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testSetNullOnDelete(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->create('product'),
            'name' => 'test',
            'productNumber' => Uuid::randomHex(),
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false],
            ],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'featureSet' => [
                'id' => $ids->create('feature-set'),
                'translations' => [
                    Defaults::LANGUAGE_SYSTEM => [
                        'name' => 'Test feature set',
                        'description' => 'Lorem ipsum dolor sit amet',
                    ],
                ],
            ],
        ];

        static::getContainer()->get('product.repository')
            ->create([$data], Context::createDefaultContext());

        $exists = static::getContainer()
            ->get(Connection::class)
            ->fetchOne(
                'SELECT id FROM product_feature_set WHERE id = :id',
                ['id' => Uuid::fromHexToBytes($ids->get('feature-set'))]
            );

        static::assertEquals($exists, Uuid::fromHexToBytes($ids->get('feature-set')));

        $delete = ['id' => $ids->get('feature-set')];

        static::getContainer()->get('product_feature_set.repository')
            ->delete([$delete], Context::createDefaultContext());

        $exists = static::getContainer()
            ->get(Connection::class)
            ->fetchOne(
                'SELECT id FROM product_feature_set WHERE id = :id',
                ['id' => Uuid::fromHexToBytes($ids->get('feature-set'))]
            );

        static::assertFalse($exists);

        $foreignKey = static::getContainer()
            ->get(Connection::class)
            ->fetchOne(
                'SELECT product_feature_set_id FROM product WHERE id = :id',
                ['id' => Uuid::fromHexToBytes($ids->get('product'))]
            );

        static::assertNull($foreignKey);
    }

    public function testNameIsRequired(): void
    {
        $ids = new IdsCollection();

        $data = [
            'id' => $ids->create('feature-set'),
        ];

        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('该变量不应为空。');

        static::getContainer()->get('product_feature_set.repository')
            ->create([$data], Context::createDefaultContext());
    }
}
