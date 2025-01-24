<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Sitemap\Provider;

use Cicada\Core\Content\LandingPage\LandingPageCollection;
use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Cicada\Core\Content\Sitemap\Provider\LandingPageUrlProvider;
use Cicada\Core\Content\Sitemap\Service\ConfigHandler;
use Cicada\Core\Content\Sitemap\Struct\Url;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\Seo\StorefrontSalesChannelTestHelper;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Framework\Seo\SeoUrlRoute\ProductPageSeoUrlRoute;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('discovery')]
class LandingPageUrlProviderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontSalesChannelTestHelper;

    private SalesChannelContext $salesChannelContext;

    private LandingPageUrlProvider $landingPageUrlProvider;

    /**
     * @var EntityRepository<LandingPageCollection>
     */
    private EntityRepository $landingPageRepository;

    protected function setUp(): void
    {
        if (!static::getContainer()->has(ProductPageSeoUrlRoute::class)) {
            static::markTestSkipped('NEXT-16799: Sitemap module has a dependency on storefront routes');
        }

        $this->landingPageRepository = static::getContainer()->get('landing_page.repository');

        $this->salesChannelContext = $this->createStorefrontSalesChannelContext(
            Uuid::randomHex(),
            'test-landing-pages-sitemap',
        );

        $this->landingPageUrlProvider = new LandingPageUrlProvider(
            static::getContainer()->get(ConfigHandler::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(RouterInterface::class),
        );
    }

    public function testLandingPageUrlIsCorrect(): void
    {
        $this->createLandingPages();

        $urlResult = $this->landingPageUrlProvider->getUrls($this->salesChannelContext, 20);

        static::assertCount(10, $urlResult->getUrls());

        $invalidUrl = array_filter($urlResult->getUrls(), function (Url $url) {
            return \in_array($url->getLoc(), [
                '/landing-page-11',
                '/landing-page-12',
                '/landing-page-13',
            ], true);
        });

        static::assertCount(0, $invalidUrl);

        [$firstUrl] = $urlResult->getUrls();

        static::assertSame('daily', $firstUrl->getChangefreq());
        static::assertSame(0.5, $firstUrl->getPriority());
        static::assertSame(LandingPageEntity::class, $firstUrl->getResource());
        static::assertTrue(Uuid::isValid($firstUrl->getIdentifier()));
    }

    public function testExcludedUrlsAreNotReturned(): void
    {
        $excludedId = Uuid::randomHex();

        $configHandler = $this->createMock(ConfigHandler::class);
        $configHandler->method('get')->with(ConfigHandler::EXCLUDED_URLS_KEY)->willReturn([
            [
                'resource' => LandingPageEntity::class,
                'salesChannelId' => $this->salesChannelContext->getSalesChannelId(),
                'identifier' => $excludedId,
            ],
        ]);

        $this->landingPageRepository->upsert([
            [
                'id' => $excludedId,
                'name' => 'Landing page 1',
                'url' => 'landing-page-1',
                'active' => true,
                'versionId' => Defaults::LIVE_VERSION,
                'salesChannels' => [
                    ['id' => $this->salesChannelContext->getSalesChannelId()],
                ],
            ],
            [
                'name' => 'Landing page 2',
                'url' => 'landing-page-2',
                'active' => true,
                'versionId' => Defaults::LIVE_VERSION,
                'salesChannels' => [
                    ['id' => $this->salesChannelContext->getSalesChannelId()],
                ],
            ],
        ], $this->salesChannelContext->getContext());

        $landingPageUrlProvider = new LandingPageUrlProvider(
            $configHandler,
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(RouterInterface::class),
        );

        $urlResult = $landingPageUrlProvider->getUrls($this->salesChannelContext, 20);

        static::assertCount(1, $urlResult->getUrls());
        static::assertSame('landing-page-2', $urlResult->getUrls()[0]->getLoc());
    }

    public function testNoSeoPathInfo(): void
    {
        $id = Uuid::randomHex();

        $this->landingPageRepository->upsert([
            [
                'id' => $id,
                'name' => 'Landing page 1',
                'url' => 'landing-page-1',
                'active' => true,
                'versionId' => Defaults::LIVE_VERSION,
                'salesChannels' => [
                    ['id' => $this->salesChannelContext->getSalesChannelId()],
                ],
            ],
        ], $this->salesChannelContext->getContext());

        // we delete the seo url to test the fallback
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('foreignKey', $id));

        /** @var EntityRepository<SeoUrlCollection> $seuUrlRepository */
        $seuUrlRepository = static::getContainer()->get('seo_url.repository');

        /** @var SeoUrlEntity|null $seoUrl */
        $seoUrl = $seuUrlRepository->search($criteria, $this->salesChannelContext->getContext())->first();

        static::assertNotNull($seoUrl);

        $seuUrlRepository->delete([
            [
                'id' => $seoUrl->getId(),
            ],
        ], $this->salesChannelContext->getContext());

        $urlResult = $this->landingPageUrlProvider->getUrls($this->salesChannelContext, 20);
        [$firstUrl] = $urlResult->getUrls();

        static::assertCount(1, $urlResult->getUrls());
        static::assertSame('/landingPage/' . $id, $firstUrl->getLoc());

        // we add a custom seo url
        $seuUrlRepository->upsert([
            [
                'routeName' => 'frontend.landing.page',
                'foreignKey' => $id,
                'pathInfo' => '/landingPage/1',
                'seoPathInfo' => 'seo-landing-page-1',
                'salesChannelId' => $this->salesChannelContext->getSalesChannelId(),
                'isCanonical' => true,
                'isModified' => true,
            ],
        ], $this->salesChannelContext->getContext());

        $urlResult = $this->landingPageUrlProvider->getUrls($this->salesChannelContext, 20);
        [$firstUrl] = $urlResult->getUrls();

        static::assertCount(1, $urlResult->getUrls());
        static::assertSame('seo-landing-page-1', $firstUrl->getLoc());
    }

    public function testReturnedOffsetIsCorrect(): void
    {
        $this->createLandingPages();

        // first run
        $urlResult = $this->landingPageUrlProvider->getUrls($this->salesChannelContext, 3);
        static::assertCount(3, $urlResult->getUrls());
        static::assertEquals(3, $urlResult->getNextOffset());

        // 1+n run
        $urlResult = $this->landingPageUrlProvider->getUrls($this->salesChannelContext, 2, $urlResult->getNextOffset());
        static::assertCount(2, $urlResult->getUrls());
        static::assertEquals(5, $urlResult->getNextOffset());

        // last run
        $urlResult = $this->landingPageUrlProvider->getUrls($this->salesChannelContext, 100, $urlResult->getNextOffset()); // test with high number to get last chunk
        static::assertNull($urlResult->getNextOffset());
    }

    private function createLandingPages(): void
    {
        // add valid landing pages
        for ($i = 1; $i <= 10; ++$i) {
            $validLandingPages[] = [
                'name' => 'Landing page ' . $i,
                'url' => 'landing-page-' . $i,
                'active' => true,
                'versionId' => Defaults::LIVE_VERSION,
                'salesChannels' => [
                    ['id' => $this->salesChannelContext->getSalesChannelId()],
                ],
            ];
        }

        $this->landingPageRepository->upsert(
            $validLandingPages,
            $this->salesChannelContext->getContext()
        );

        $newSalesChannelContext = $this->createStorefrontSalesChannelContext(
            Uuid::randomHex(),
            'new-landing-pages-sitemap',
        );

        // add invalid landing pages
        $this->landingPageRepository->upsert([
            // different sales channel
            [
                'name' => 'Landing page 11',
                'url' => 'landing-page-11',
                'active' => true,
                'versionId' => Defaults::LIVE_VERSION,
                'salesChannels' => [
                    ['id' => $newSalesChannelContext->getSalesChannelId()],
                ],
            ],
            // not active
            [
                'name' => 'Landing page 12',
                'url' => 'landing-page-12',
                'active' => false,
                'versionId' => Defaults::LIVE_VERSION,
                'salesChannels' => [
                    ['id' => $this->salesChannelContext->getSalesChannelId()],
                ],
            ],
            // not live version
            [
                'name' => 'Landing page 13',
                'url' => 'landing-page-13',
                'active' => true,
                'versionId' => Uuid::randomHex(),
                'salesChannels' => [
                    ['id' => $this->salesChannelContext->getSalesChannelId()],
                ],
            ],
        ], Context::createDefaultContext());
    }
}
