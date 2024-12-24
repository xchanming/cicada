<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Page\Wishlist\GuestWishlistPageLoadedEvent;
use Cicada\Storefront\Page\Wishlist\GuestWishlistPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class GuestWishlistPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    public function testItLoadsWishlistGuestPage(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContext();

        $event = null;
        $this->catchEvent(GuestWishlistPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        self::assertPageEvent(GuestWishlistPageLoadedEvent::class, $event, $context, $request, $page);
    }

    protected function getPageLoader(): GuestWishlistPageLoader
    {
        return static::getContainer()->get(GuestWishlistPageLoader::class);
    }
}
