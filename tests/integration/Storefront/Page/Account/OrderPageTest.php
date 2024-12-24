<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Account;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Page\Account\Order\AccountOrderPageLoadedEvent;
use Cicada\Storefront\Page\Account\Order\AccountOrderPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class OrderPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    public function testItLoadsOrders(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $event = null;
        $this->catchEvent(AccountOrderPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertCount(0, $page->getOrders());
        self::assertPageEvent(AccountOrderPageLoadedEvent::class, $event, $context, $request, $page);
    }

    public function testSalesChannelRestriction(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $testContext = $this->createSalesChannelContext();

        $this->placeRandomOrder($context);
        $order = $this->placeRandomOrder($context);
        static::getContainer()->get('order.repository')->update([
            [
                'id' => $order,
                'salesChannelId' => $testContext->getSalesChannel()->getId(),
            ],
        ], $context->getContext());

        $event = null;
        $this->catchEvent(AccountOrderPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertCount(1, $page->getOrders());
        self::assertPageEvent(AccountOrderPageLoadedEvent::class, $event, $context, $request, $page);
    }

    protected function getPageLoader(): AccountOrderPageLoader
    {
        return static::getContainer()->get(AccountOrderPageLoader::class);
    }
}
