<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\Cms;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Page\Cms\CmsPageLoadedHook;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(CmsPageLoadedHook::class)]
class CmsPageLoadedHookTest extends TestCase
{
    public function testCmsPageLoadedHook(): void
    {
        $page = new CmsPageEntity();
        $hook = new CmsPageLoadedHook($page, Generator::createSalesChannelContext());
        static::assertSame('cms-page-loaded', $hook->getName());
        static::assertSame($page, $hook->getPage());
    }
}
