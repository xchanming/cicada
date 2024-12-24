<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Account;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Page\Account\Profile\AccountProfilePageLoadedEvent;
use Cicada\Storefront\Page\Account\Profile\AccountProfilePageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ProfilePageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    public function testItLoadsTheProfilePage(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $event = null;
        $this->catchEvent(AccountProfilePageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        self::assertPageEvent(AccountProfilePageLoadedEvent::class, $event, $context, $request, $page);
    }

    protected function getPageLoader(): AccountProfilePageLoader
    {
        return static::getContainer()->get(AccountProfilePageLoader::class);
    }
}
