<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\SalesChannel;

use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Content\Cms\SalesChannel\CmsRouteResponse;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(CmsRouteResponse::class)]
class CmsRouteResponseTest extends TestCase
{
    public function testGetCmsPage(): void
    {
        $expected = new CmsPageEntity();
        $response = new CmsRouteResponse($expected);

        $actual = $response->getCmsPage();
        static::assertSame($expected, $actual);
    }
}
