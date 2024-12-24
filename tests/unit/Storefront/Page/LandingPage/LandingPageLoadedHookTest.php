<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\LandingPage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Page\LandingPage\LandingPage;
use Cicada\Storefront\Page\LandingPage\LandingPageLoadedHook;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(LandingPageLoadedHook::class)]
class LandingPageLoadedHookTest extends TestCase
{
    public function testLandingPageLoadedHook(): void
    {
        $page = new LandingPage();
        $context = Generator::createSalesChannelContext();

        $hook = new LandingPageLoadedHook($page, $context);
        static::assertSame('landing-page-loaded', $hook->getName());
        static::assertSame($page, $hook->getPage());
    }
}
