<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Sitemap\SalesChannel;

use Cicada\Core\Content\Sitemap\SalesChannel\SitemapFileRoute;
use Cicada\Core\Framework\Extensions\ExtensionDispatcher;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Tests\Examples\GetSitemapFileExample;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(SitemapFileRoute::class)]
class SitemapFileRouteTest extends TestCase
{
    public function testExtension(): void
    {
        $fileSystem = $this->createMock(FilesystemOperator::class);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new GetSitemapFileExample());

        $extensionDispatcher = new ExtensionDispatcher($dispatcher);

        $route = new SitemapFileRoute($fileSystem, $extensionDispatcher);

        $request = new Request();
        $context = $this->createMock(SalesChannelContext::class);
        $filePath = 'test.xml.gz';

        $response = $route->getSitemapFile($request, $context, $filePath);

        static::assertEquals('Hello World!', $response->getContent());
    }
}
