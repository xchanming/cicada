<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Sitemap\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Sitemap\Provider\CustomUrlProvider;
use Cicada\Core\Content\Sitemap\Service\ConfigHandler;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(CustomUrlProvider::class)]
class CustomUrlProviderTest extends TestCase
{
    public function testGetUrlsReturnsNoUrls(): void
    {
        $configHandlerStub = $this->createMock(ConfigHandler::class);
        $configHandlerStub->method('get')
            ->with(ConfigHandler::CUSTOM_URLS_KEY)
            ->willReturn([]);

        $customUrlProvider = $this->getCustomUrlProvider($configHandlerStub);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::assertSame([], $customUrlProvider->getUrls($salesChannelContext, 100)->getUrls());
    }

    public function testGetUrlsReturnsAllUrlsForSalesChannel(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $configHandlerStub = $this->createMock(ConfigHandler::class);
        $configHandlerStub->method('get')
            ->with(ConfigHandler::CUSTOM_URLS_KEY)
            ->willReturn([
                [
                    'url' => 'foo',
                    'lastMod' => new \DateTimeImmutable(),
                    'changeFreq' => 'weekly',
                    'priority' => 0.5,
                    'salesChannelId' => 2,
                ], [
                    'url' => 'bar',
                    'lastMod' => new \DateTimeImmutable(),
                    'changeFreq' => 'weekly',
                    'priority' => 0.5,
                    'salesChannelId' => $salesChannelContext->getSalesChannel()->getId(),
                ],
            ]);

        $customUrlProvider = $this->getCustomUrlProvider($configHandlerStub);

        static::assertCount(1, $customUrlProvider->getUrls($salesChannelContext, 100)->getUrls());
    }

    public function testGetUrlsReturnsAllUrlsForSalesChannelIdNull(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $configHandlerStub = $this->createMock(ConfigHandler::class);
        $configHandlerStub->method('get')
            ->with(ConfigHandler::CUSTOM_URLS_KEY)
            ->willReturn([
                [
                    'url' => 'foo',
                    'lastMod' => new \DateTimeImmutable(),
                    'changeFreq' => 'weekly',
                    'priority' => 0.5,
                    'salesChannelId' => 2,
                ], [
                    'url' => 'bar',
                    'lastMod' => new \DateTimeImmutable(),
                    'changeFreq' => 'weekly',
                    'priority' => 0.5,
                    'salesChannelId' => null,
                ], [
                    'url' => 'fooBar',
                    'lastMod' => new \DateTimeImmutable(),
                    'changeFreq' => 'weekly',
                    'priority' => 0.5,
                    'salesChannelId' => null,
                ],
            ]);

        $customUrlProvider = $this->getCustomUrlProvider($configHandlerStub);

        $urls = $customUrlProvider->getUrls($salesChannelContext, 100)->getUrls();

        [$firstUrl, $secondUrl] = $urls;
        static::assertCount(2, $urls);
        static::assertSame('bar', $firstUrl->getLoc());
        static::assertSame('fooBar', $secondUrl->getLoc());
    }

    public function testGetUrlsReturnsNoUrlsWrongSalesChannelId(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $configHandlerStub = $this->createMock(ConfigHandler::class);
        $configHandlerStub->method('get')
            ->with(ConfigHandler::CUSTOM_URLS_KEY)
            ->willReturn([
                [
                    'url' => 'foo',
                    'lastMod' => new \DateTimeImmutable(),
                    'changeFreq' => 'weekly',
                    'priority' => 0.5,
                    'salesChannelId' => 2,
                ],
            ]);

        $customUrlProvider = $this->getCustomUrlProvider($configHandlerStub);

        static::assertEmpty($customUrlProvider->getUrls($salesChannelContext, 100)->getUrls());
    }

    private function getCustomUrlProvider(ConfigHandler $configHandlerStub): CustomUrlProvider
    {
        return new CustomUrlProvider($configHandlerStub);
    }
}
