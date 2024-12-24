<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Checkout;

use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Cicada\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Cicada\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoader;
use Cicada\Storefront\Page\Checkout\Finish\CheckoutFinishPageOrderCriteriaEvent;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class FinishPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    public function testItRequiresAOrderId(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->expectParamMissingException('orderId');
        $this->getPageLoader()->load($request, $context);
    }

    public function testMissingOrderThrows(): void
    {
        $request = new Request([], [], ['orderId' => 'foo']);
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->expectException(OrderException::class);

        $this->getPageLoader()->load($request, $context);
    }

    public function testFinishPageLoading(): void
    {
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $orderId = $this->placeRandomOrder($context);
        $request = new Request([], [], ['orderId' => $orderId]);
        $eventWasThrown = false;
        $criteria = new Criteria([$orderId]);

        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            CheckoutFinishPageOrderCriteriaEvent::class,
            static function (CheckoutFinishPageOrderCriteriaEvent $event) use ($criteria, &$eventWasThrown): void {
                static::assertSame($criteria->getIds(), $event->getCriteria()->getIds());
                $eventWasThrown = true;
            }
        );

        $event = null;
        $this->catchEvent(CheckoutFinishPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertInstanceOf(CheckoutFinishPage::class, $page);
        static::assertSame(13.04, $page->getOrder()->getPrice()->getNetPrice());
        self::assertPageEvent(CheckoutFinishPageLoadedEvent::class, $event, $context, $request, $page);
        static::assertTrue($eventWasThrown);

        $this->resetEventDispatcher();
    }

    /**
     * @return CheckoutFinishPageLoader
     */
    protected function getPageLoader()
    {
        return static::getContainer()->get(CheckoutFinishPageLoader::class);
    }
}
