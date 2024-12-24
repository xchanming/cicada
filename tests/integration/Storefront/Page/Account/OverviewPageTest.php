<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Account;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Page\Account\Overview\AccountOverviewPageLoadedEvent;
use Cicada\Storefront\Page\Account\Overview\AccountOverviewPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class OverviewPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;
    private const LAST_TRANSACTION_ID = '00000000000000000000000000000000';

    public function testItLoadsTheOverview(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $orderId = $this->placeRandomOrder($context);
        static::getContainer()->get('order_transaction.repository')->create([
            [
                // this id would result in being the first transaction with wrong sorting
                'id' => self::LAST_TRANSACTION_ID,
                'orderId' => $orderId,
                'amount' => new CalculatedPrice(10.0, 10.0, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'paymentMethodId' => $this->getValidPaymentMethodId(),
                'stateId' => $this->getStateMachineState(OrderTransactionStates::STATE_MACHINE, OrderTransactionStates::STATE_OPEN),
            ],
        ], $context->getContext());

        $event = null;
        $this->catchEvent(AccountOverviewPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context, $this->createCustomer());

        $order = $page->getNewestOrder();
        static::assertInstanceOf(OrderEntity::class, $order);
        $transactions = $order->getTransactions();
        static::assertNotNull($transactions);
        static::assertCount(2, $transactions);
        $transaction = $transactions->last();
        static::assertNotNull($transaction);
        static::assertSame(self::LAST_TRANSACTION_ID, $transaction->getId());
        self::assertPageEvent(AccountOverviewPageLoadedEvent::class, $event, $context, $request, $page);
    }

    public function testSalesChannelRestriction(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $testContext = $this->createSalesChannelContext();

        $order = $this->placeRandomOrder($context);
        static::getContainer()->get('order.repository')->update([
            [
                'id' => $order,
                'salesChannelId' => $testContext->getSalesChannel()->getId(),
            ],
        ], $context->getContext());

        $event = null;
        $this->catchEvent(AccountOverviewPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context, $this->createCustomer());

        static::assertNull($page->getNewestOrder());
        self::assertPageEvent(AccountOverviewPageLoadedEvent::class, $event, $context, $request, $page);
    }

    protected function getPageLoader(): AccountOverviewPageLoader
    {
        return static::getContainer()->get(AccountOverviewPageLoader::class);
    }
}
