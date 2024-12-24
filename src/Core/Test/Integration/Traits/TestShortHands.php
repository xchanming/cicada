<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\Traits;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Cicada\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Integration\Builder\Customer\CustomerBuilder;
use Cicada\Core\Test\Integration\Helper\MailEventListener;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
trait TestShortHands
{
    use KernelTestBehaviour;

    /**
     * @param array<string, mixed> $options
     */
    protected function getContext(?string $token = null, array $options = [], string $salesChannelId = TestDefaults::SALES_CHANNEL): SalesChannelContext
    {
        $token ??= Uuid::randomHex();

        return static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, $salesChannelId, $options);
    }

    protected function addProductToCart(string $id, SalesChannelContext $context): Cart
    {
        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $id, 'referencedId' => $id], $context);

        $cart = static::getContainer()->get(CartService::class)
            ->getCart($context->getToken(), $context);

        return static::getContainer()->get(CartService::class)
            ->add($cart, $product, $context);
    }

    protected function order(Cart $cart, SalesChannelContext $context, ?RequestDataBag $data = null): string
    {
        return static::getContainer()->get(CartService::class)
            ->order($cart, $context, $data ?? new RequestDataBag());
    }

    protected function assertProductInOrder(string $orderId, string $productId): OrderLineItemEntity
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $criteria->addFilter(new AndFilter([
            new EqualsFilter('referencedId', $productId),
            new EqualsFilter('type', LineItem::PRODUCT_LINE_ITEM_TYPE),
            new EqualsFilter('orderId', $orderId),
        ]));

        $exists = static::getContainer()->get('order_line_item.repository')
            ->search($criteria, Context::createDefaultContext());

        static::assertCount(1, $exists);

        $item = $exists->first();

        static::assertInstanceOf(OrderLineItemEntity::class, $item);

        return $item;
    }

    protected function assertLineItemTotalPrice(Cart $cart, string $id, float $price): void
    {
        $item = $cart->get($id);

        static::assertInstanceOf(LineItem::class, $item, \sprintf('Can not find line item with id %s', $id));

        static::assertInstanceOf(CalculatedPrice::class, $item->getPrice(), \sprintf('Line item with id %s has no price', $id));

        static::assertEquals($price, $item->getPrice()->getTotalPrice(), \sprintf('Line item with id %s has wrong total price', $id));
    }

    protected function assertLineItemUnitPrice(Cart $cart, string $id, float $price): void
    {
        $item = $cart->get($id);

        static::assertInstanceOf(LineItem::class, $item, \sprintf('Can not find line item with id %s', $id));

        static::assertInstanceOf(CalculatedPrice::class, $item->getPrice(), \sprintf('Line item with id %s has no price', $id));

        static::assertEquals($price, $item->getPrice()->getUnitPrice(), \sprintf('Line item with id %s has wrong unit price', $id));
    }

    protected function assertLineItemInCart(Cart $cart, string $id): void
    {
        $item = $cart->get($id);

        static::assertInstanceOf(LineItem::class, $item, \sprintf('Can not find line item with id %s', $id));
    }

    protected function login(SalesChannelContext $context, ?string $customerId = null): SalesChannelContext
    {
        if ($customerId === null) {
            $customer = new CustomerBuilder(
                new IdsCollection(),
                Uuid::randomHex(),
                $context->getSalesChannelId()
            );

            static::getContainer()->get('customer.repository')->create(
                [$customer->build()],
                Context::createDefaultContext()
            );

            $customerId = $customer->id;
        }

        return $this->getContext($context->getToken(), [
            SalesChannelContextService::CUSTOMER_ID => $customerId,
        ], $context->getSalesChannelId());
    }

    protected function assertMailSent(MailEventListener $listener, string $type): void
    {
        static::assertTrue($listener->sent($type), \sprintf('Mail with type %s was not sent', $type));
    }

    /**
     * @return mixed
     */
    protected function mailListener(\Closure $closure)
    {
        $mapping = static::getContainer()->get(Connection::class)
            ->fetchAllKeyValue('SELECT LOWER(HEX(id)), technical_name FROM mail_template_type');

        $listener = new MailEventListener($mapping);

        $dispatcher = static::getContainer()->get('event_dispatcher');

        $dispatcher->addListener(FlowSendMailActionEvent::class, $listener);

        $result = $closure($listener);

        $dispatcher->removeListener(FlowSendMailActionEvent::class, $listener);

        return $result;
    }

    private function assertStock(string $productId, int $stock, int $available): void
    {
        /** @var array{stock: int, available_stock:int} $stocks */
        $stocks = static::getContainer()->get(Connection::class)->fetchAssociative(
            'SELECT stock, available_stock FROM product WHERE id = :id',
            ['id' => Uuid::fromHexToBytes($productId)]
        );

        static::assertNotEmpty($stocks, \sprintf('Product with id %s not found', $productId));

        static::assertEquals($stock, (int) $stocks['stock'], \sprintf('Product with id %s has wrong stock', $productId));

        static::assertEquals($available, $stocks['available_stock'], \sprintf('Product with id %s has wrong available stock', $productId));
    }
}
