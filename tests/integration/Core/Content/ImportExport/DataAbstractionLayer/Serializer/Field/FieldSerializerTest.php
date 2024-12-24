<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\EntitySerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field\FieldSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('services-settings')]
class FieldSerializerTest extends TestCase
{
    use KernelTestBehaviour;

    public function testEmptyValidOneToManyAssociation(): void
    {
        $fieldSerializer = $this->getFieldSerializer();

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('deliveries', OrderDeliveryEntity::class, 'order_delivery');
        $registry = new DefinitionInstanceRegistry(static::getContainer(), [OrderDeliveryEntity::class => OrderDeliveryDefinition::class], []);
        $field->compile($registry);

        $result = \iterator_to_array($fieldSerializer->serialize($config, $field, []));
        static::assertSame(['deliveries' => []], $result);
    }

    public function testValidOneToManyAssociation(): void
    {
        $fieldSerializer = $this->getFieldSerializer();

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('deliveries', OrderDeliveryEntity::class, 'order_delivery');
        $registry = new DefinitionInstanceRegistry(static::getContainer(), [OrderDeliveryEntity::class => OrderDeliveryDefinition::class], []);
        $field->compile($registry);

        $delivery = new OrderDeliveryEntity();
        $deliveryId = Uuid::randomHex();
        $delivery->setId($deliveryId);

        $result = \iterator_to_array($fieldSerializer->serialize($config, $field, new OrderDeliveryCollection([$delivery])));
        static::assertSame([
            'deliveries' => [
                '_uniqueIdentifier' => $deliveryId,
                'versionId' => null,
                'translated' => [],
                'orderId' => null,
                'orderVersionId' => null,
                'shippingOrderAddressId' => null,
                'shippingOrderAddressVersionId' => null,
                'shippingMethodId' => null,
                'trackingCodes' => null,
                'shippingCosts' => null,
                'stateId' => null,
                'customFields' => null,
                'id' => $deliveryId,
            ],
        ], $result);
    }

    public function testInvalidEntityOneToManyAssociation(): void
    {
        $fieldSerializer = $this->getFieldSerializer();

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('deliveries', OrderEntity::class, 'order_delivery');
        $registry = new DefinitionInstanceRegistry(static::getContainer(), [OrderEntity::class => OrderDefinition::class], []);
        $field->compile($registry);

        $delivery = new OrderDeliveryEntity();
        $deliveryId = Uuid::randomHex();
        $delivery->setId($deliveryId);

        $result = \iterator_to_array($fieldSerializer->serialize($config, $field, new OrderDeliveryCollection([$delivery])));
        static::assertEmpty($result);
    }

    public function testInvalidPropertyNameOneToManyAssociation(): void
    {
        $fieldSerializer = $this->getFieldSerializer();

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('foo', OrderEntity::class, 'order_delivery');
        $registry = new DefinitionInstanceRegistry(static::getContainer(), [OrderEntity::class => OrderDefinition::class], []);
        $field->compile($registry);

        $delivery = new OrderDeliveryEntity();
        $deliveryId = Uuid::randomHex();
        $delivery->setId($deliveryId);

        $result = \iterator_to_array($fieldSerializer->serialize($config, $field, new OrderDeliveryCollection([$delivery])));
        static::assertEmpty($result);
    }

    public function testNullValueOneToManyAssociation(): void
    {
        $fieldSerializer = $this->getFieldSerializer();

        $config = new Config([], [], []);

        $field = new OneToManyAssociationField('deliveries', OrderEntity::class, 'order_delivery');
        $registry = new DefinitionInstanceRegistry(static::getContainer(), [OrderEntity::class => OrderDefinition::class], []);
        $field->compile($registry);

        $result = \iterator_to_array($fieldSerializer->serialize($config, $field, null));
        static::assertEmpty($result);
    }

    private function getFieldSerializer(): FieldSerializer
    {
        $fieldSerializer = new FieldSerializer();
        $serializerRegistry = new SerializerRegistry([new EntitySerializer()], [$fieldSerializer]);
        $fieldSerializer->setRegistry($serializerRegistry);

        return $fieldSerializer;
    }
}
