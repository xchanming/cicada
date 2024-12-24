<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Document\Renderer;

use Cicada\Core\Checkout\Document\Renderer\OrderDocumentCriteriaFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderDocumentCriteriaFactory::class)]
class OrderDocumentCriteriaFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $id = Uuid::randomHex();

        $criteria = OrderDocumentCriteriaFactory::create([$id], 'test');

        static::assertEquals($id, $criteria->getIds()[0]);

        $associations = $criteria->getAssociations();

        static::assertArrayHasKey('lineItems', $associations);
        static::assertArrayHasKey('transactions', $associations);
        static::assertArrayHasKey('currency', $associations);
        static::assertArrayHasKey('language', $associations);
        static::assertArrayHasKey('addresses', $associations);
        static::assertArrayHasKey('orderCustomer', $associations);

        $transactionCriteria = $associations['transactions'];
        static::assertInstanceOf(Criteria::class, $transactionCriteria);
        static::assertInstanceOf(Criteria::class, $transactionCriteria->getAssociations()['paymentMethod']);

        $languageCriteria = $associations['language'];
        static::assertInstanceOf(Criteria::class, $languageCriteria);
        static::assertInstanceOf(Criteria::class, $languageCriteria->getAssociations()['locale']);

        $addressesCriteria = $associations['addresses'];
        static::assertInstanceOf(Criteria::class, $addressesCriteria);
        static::assertInstanceOf(Criteria::class, $addressesCriteria->getAssociations()['country']);

        $orderCustomerCriteria = $associations['orderCustomer'];
        static::assertInstanceOf(Criteria::class, $orderCustomerCriteria);
        static::assertInstanceOf(Criteria::class, $orderCustomerCriteria->getAssociations()['customer']);

        $deliveryCriteria = $associations['deliveries'];
        static::assertInstanceOf(Criteria::class, $deliveryCriteria);
        static::assertInstanceOf(Criteria::class, $deliveryCriteria->getAssociations()['shippingMethod']);
        static::assertInstanceOf(Criteria::class, $deliveryCriteria->getAssociations()['positions']);
        static::assertInstanceOf(Criteria::class, $deliveryCriteria->getAssociations()['shippingOrderAddress']);

        $shippingAddressCriteria = $deliveryCriteria->getAssociations()['shippingOrderAddress'];
        static::assertInstanceOf(Criteria::class, $shippingAddressCriteria->getAssociations()['country']);
    }
}
