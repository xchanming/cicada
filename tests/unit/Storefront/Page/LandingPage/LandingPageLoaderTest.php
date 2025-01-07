<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\LandingPage;

use Cicada\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockCollection;
use Cicada\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Cicada\Core\Content\Cms\Aggregate\CmsSection\CmsSectionCollection;
use Cicada\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Content\LandingPage\SalesChannel\LandingPageRoute;
use Cicada\Core\Content\LandingPage\SalesChannel\LandingPageRouteResponse;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Page\GenericPageLoader;
use Cicada\Storefront\Page\LandingPage\LandingPageLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(LandingPageLoader::class)]
class LandingPageLoaderTest extends TestCase
{
    public function testNoLandingPageIdException(): void
    {
        $landingPageRouteMock = $this->createMock(LandingPageRoute::class);
        $landingPageRouteMock->expects(static::never())->method('load');

        $landingPageLoader = new LandingPageLoader(
            $this->createMock(GenericPageLoader::class),
            $landingPageRouteMock,
            $this->createMock(EventDispatcherInterface::class)
        );

        $request = new Request([], [], []);
        $salesChannelContext = $this->getSalesChannelContext();

        static::expectExceptionObject(RoutingException::missingRequestParameter('landingPageId', '/landingPageId'));
        $landingPageLoader->load($request, $salesChannelContext);
    }

    public function testNoLandingPageException(): void
    {
        $landingPageRouteMock = $this->createMock(LandingPageRoute::class);
        $landingPageRouteMock->expects(static::once())->method('load');

        $landingPageLoader = new LandingPageLoader(
            $this->createMock(GenericPageLoader::class),
            $landingPageRouteMock,
            $this->createMock(EventDispatcherInterface::class)
        );

        $landingPageId = Uuid::randomHex();
        $request = new Request([], [], ['landingPageId' => $landingPageId]);
        $salesChannelContext = $this->getSalesChannelContext();

        static::expectExceptionObject(new PageNotFoundException($landingPageId));
        $landingPageLoader->load($request, $salesChannelContext);
    }

    public function testItLoads(): void
    {
        $productId = Uuid::randomHex();
        $landingPageId = Uuid::randomHex();
        $request = new Request([], [], ['landingPageId' => $landingPageId]);
        $salesChannelContext = $this->getSalesChannelContext();

        $product = $this->getProduct($productId);
        $cmsPage = $this->getCmsPage($product);

        $landingPageLoader = $this->getLandingPageLoaderWithProduct($landingPageId, $cmsPage, $request, $salesChannelContext);

        $page = $landingPageLoader->load($request, $salesChannelContext);

        /** @phpstan-ignore-next-line */
        $cmsPageLoaded = $page->getLandingPage()->getCmsPage();

        static::assertEquals($cmsPage, $cmsPageLoaded);
    }

    private function getLandingPageLoaderWithProduct(string $landingPageId, CmsPageEntity $cmsPage, Request $request, SalesChannelContext $salesChannelContext): LandingPageLoader
    {
        $landingPage = new LandingPageEntity();
        $landingPage->setId($landingPageId);
        $landingPage->setCmsPage($cmsPage);

        $landingPageRouteMock = $this->createMock(LandingPageRoute::class);
        $landingPageRouteMock
            ->method('load')
            ->with($landingPageId, $request, $salesChannelContext)
            ->willReturn(new LandingPageRouteResponse($landingPage));

        return new LandingPageLoader(
            $this->createMock(GenericPageLoader::class),
            $landingPageRouteMock,
            $this->createMock(EventDispatcherInterface::class)
        );
    }

    private function getProduct(string $productId): SalesChannelProductEntity
    {
        $product = new SalesChannelProductEntity();
        $product->setId($productId);

        return $product;
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setId('salesChannelId');

        return Generator::createSalesChannelContext(
            salesChannel: $salesChannelEntity,
        );
    }

    private function getCmsPage(SalesChannelProductEntity $productEntity): CmsPageEntity
    {
        $cmsPageEntity = new CmsPageEntity();

        $cmsSectionEntity = new CmsSectionEntity();
        $cmsSectionEntity->setId(Uuid::randomHex());

        $cmsBlockEntity = new CmsBlockEntity();
        $cmsBlockEntity->setId(Uuid::randomHex());

        $cmsSlotEntity = new CmsSlotEntity();
        $cmsSlotEntity->setId(Uuid::randomHex());
        $cmsSlotEntity->setSlot(json_encode($productEntity->getTranslated(), \JSON_THROW_ON_ERROR));

        $cmsBlockEntity->setSlots(new CmsSlotCollection([$cmsSlotEntity]));
        $cmsSectionEntity->setBlocks(new CmsBlockCollection([$cmsBlockEntity]));
        $cmsPageEntity->setSections(new CmsSectionCollection([$cmsSectionEntity]));

        return $cmsPageEntity;
    }
}
