<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\Exception;

use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(PageNotFoundException::class)]
class PageNotFoundExceptionTest extends TestCase
{
    public function testPageNotFoundException(): void
    {
        $exception = new PageNotFoundException('cmsPageId');

        static::assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
        static::assertSame(PageNotFoundException::ERROR_CODE, $exception->getErrorCode());
        static::assertSame('Page with id "cmsPageId" was not found.', $exception->getMessage());
        static::assertSame(['pageId' => 'cmsPageId'], $exception->getParameters());
    }
}
