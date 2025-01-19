<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Order;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\CartSerializationCleaner;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Order\OrderConverter;
use Cicada\Core\Checkout\Cart\Order\OrderPersister;
use Cicada\Core\Checkout\Cart\Order\OrderPersisterInterface;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Processor;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\TestDefaults;
use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class OrderPersisterTest extends TestCase
{
    use IntegrationTestBehaviour;

    private OrderPersisterInterface $orderPersister;

    private Processor $cartProcessor;

    private OrderConverter $orderConverter;

    private CartSerializationCleaner $serializationCleaner;

    protected function setUp(): void
    {
        $this->orderPersister = static::getContainer()->get(OrderPersister::class);
        $this->cartProcessor = static::getContainer()->get(Processor::class);
        $this->orderConverter = static::getContainer()->get(OrderConverter::class);
        $this->serializationCleaner = static::getContainer()->get(CartSerializationCleaner::class);
    }

    public function testSave(): void
    {
        $cart = new Cart(Uuid::randomHex());
        $cart->add(
            (new LineItem('test', 'test'))
                ->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()))
                ->setLabel('test')
        )->add(
            (new LineItem('test2', 'test'))
                ->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()))
                ->setLabel('test2')
        );
        $positionByIdentifier = [
            'test' => 1,
            'test2' => 2,
        ];

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(static::once())
            ->method('create')
            ->with(
                static::callback(function (array $payload) use ($positionByIdentifier) {
                    foreach ($payload[0]['lineItems'] as $lineItem) {
                        if ($positionByIdentifier[$lineItem['identifier']] !== $lineItem['position']) {
                            return false;
                        }
                    }

                    return true;
                })
            );
        $order = new OrderEntity();
        $order->setUniqueIdentifier(Uuid::randomHex());
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                'order',
                1,
                new EntityCollection([$order]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $persister = new OrderPersister($repository, $this->orderConverter, $this->serializationCleaner);

        $persister->persist($cart, $this->getSalesChannelContext());
    }

    public function testSaveWithMissingLabel(): void
    {
        $cart = new Cart('a-b-c');
        $cart->add(
            (new LineItem('test', LineItem::CREDIT_LINE_ITEM_TYPE))
                ->setPriceDefinition(new AbsolutePriceDefinition(1))
        );

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $processedCart = $this->cartProcessor->process($cart, $context, new CartBehavior());

        $exception = null;

        try {
            $this->orderPersister->persist($processedCart, $context);
        } catch (CartException $exception) {
        }

        static::assertInstanceOf(CartException::class, $exception);
        static::assertStringContainsString('Line item "test" incomplete. Property "label" missing.', $exception->getMessage());
    }

    private function getCustomer(): CustomerEntity
    {
        $faker = Factory::create();

        $billingAddress = new CustomerAddressEntity();
        $billingAddress->setId('SWAG-ADDRESS-ID-1');
        $billingAddress->setSalutationId($this->getValidSalutationId());
        $billingAddress->setName($faker->name());
        $billingAddress->setStreet($faker->streetAddress());
        $billingAddress->setZipcode($faker->postcode());
        $billingAddress->setCountryId('SWAG-AREA-COUNTRY-ID-1');

        $customer = new CustomerEntity();
        $customer->setId('SWAG-CUSTOMER-ID-1');
        $customer->setDefaultBillingAddress($billingAddress);
        $customer->setEmail('test@example.com');
        $customer->setSalutationId($this->getValidSalutationId());
        $customer->setName($faker->name());
        $customer->setCustomerNumber('Test');

        return $customer;
    }

    private function getSalesChannelContext(): MockObject&SalesChannelContext
    {
        $customer = $this->getCustomer();
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setLanguageId(Defaults::LANGUAGE_SYSTEM);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getCustomer')->willReturn($customer);

        $context = Context::createDefaultContext();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $salesChannelContext->method('getSalesChannel')->willReturn($salesChannel);
        $salesChannelContext->method('getContext')->willReturn($context);

        return $salesChannelContext;
    }
}
