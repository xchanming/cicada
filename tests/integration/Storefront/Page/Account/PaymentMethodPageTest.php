<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Account;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Cicada\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Cicada\Tests\Integration\Storefront\Page\StorefrontPageTestConstants;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - will be removed
 *
 * @internal
 */
class PaymentMethodPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);
    }

    public function testItlLoadsTheRequestedCustomersData(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $event = null;
        $this->catchEvent(AccountPaymentMethodPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertCount(StorefrontPageTestConstants::PAYMENT_METHOD_COUNT, $page->getPaymentMethods());
        self::assertPageEvent(AccountPaymentMethodPageLoadedEvent::class, $event, $context, $request, $page);
    }

    protected function getPageLoader(): AccountPaymentMethodPageLoader
    {
        return static::getContainer()->get(AccountPaymentMethodPageLoader::class);
    }
}
