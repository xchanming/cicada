<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\LandingPage;

use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Content\LandingPage\LandingPageException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Storefront\Page\LandingPage\LandingPage;
use Cicada\Storefront\Page\LandingPage\LandingPageLoadedEvent;
use Cicada\Storefront\Page\LandingPage\LandingPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('discovery')]
class LandingPageLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    private IdsCollection $ids;

    public function testLoadWithoutId(): void
    {
        $this->expectExceptionObject(RoutingException::missingRequestParameter('landingPageId', '/landingPageId'));

        $context = $this->createSalesChannelContextWithNavigation();
        $this->getPageLoader()->load(new Request(), $context);
    }

    public function testLoad(): void
    {
        $this->ids = new IdsCollection();

        $request = new Request([], [], [
            'landingPageId' => $this->ids->get('landing-page'),
        ]);

        $context = $this->createSalesChannelContextWithNavigation();
        $this->ids->set('sales-channel', $context->getSalesChannelId());
        $this->createData();

        $event = null;
        $this->catchEvent(LandingPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertInstanceOf(LandingPage::class, $page);

        static::assertInstanceOf(LandingPageEntity::class, $page->getLandingPage());
        static::assertInstanceOf(CmsPageEntity::class, $page->getLandingPage()->getCmsPage());
        static::assertSame($this->ids->get('cms-page'), $page->getLandingPage()->getCmsPage()->getId());

        self::assertPageEvent(LandingPageLoadedEvent::class, $event, $context, $request, $page);
    }

    public function testLoadWithInactiveLandingPage(): void
    {
        $this->ids = new IdsCollection();

        $request = new Request([], [], [
            'landingPageId' => $this->ids->create('landing-page'),
        ]);
        $this->expectExceptionObject(LandingPageException::notFound($this->ids->get('landing-page')));

        $context = $this->createSalesChannelContextWithNavigation();
        $this->ids->set('sales-channel', $context->getSalesChannelId());
        $this->createData(false);

        $this->getPageLoader()->load($request, $context);
    }

    public function testLoadWithoutCmsPage(): void
    {
        $this->ids = new IdsCollection();

        $request = new Request([], [], [
            'landingPageId' => $this->ids->create('landing-page'),
        ]);
        $this->expectExceptionObject(new PageNotFoundException($this->ids->get('landing-page')));

        $context = $this->createSalesChannelContextWithNavigation();
        $this->ids->set('sales-channel', $context->getSalesChannelId());
        $this->createData(true, false);

        $this->getPageLoader()->load($request, $context);
    }

    /**
     * @return LandingPageLoader
     */
    protected function getPageLoader()
    {
        return static::getContainer()->get(LandingPageLoader::class);
    }

    private function createData(bool $isActive = true, bool $withCmsPage = true): void
    {
        $data = [
            'id' => $this->ids->create('landing-page'),
            'name' => 'Test',
            'url' => 'myUrl',
            'active' => $isActive,
            'salesChannels' => [
                [
                    'id' => $this->ids->get('sales-channel'),
                ],
            ],
            'cmsPage' => [
                'id' => $this->ids->create('cms-page'),
                'type' => 'product_list',
                'sections' => [
                    [
                        'position' => 0,
                        'type' => 'sidebar',
                        'blocks' => [
                            [
                                'type' => 'product-listing',
                                'position' => 1,
                                'slots' => [
                                    ['type' => 'product-listing', 'slot' => 'content'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if (!$withCmsPage) {
            unset($data['cmsPage']);
        }

        static::getContainer()->get('landing_page.repository')
            ->create([$data], Context::createDefaultContext());
    }
}
