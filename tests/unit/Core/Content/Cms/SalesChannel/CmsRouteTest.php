<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Cms\SalesChannel;

use Cicada\Core\Content\Cms\CmsPageCollection;
use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Content\Cms\SalesChannel\CmsRoute;
use Cicada\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(CmsRoute::class)]
class CmsRouteTest extends TestCase
{
    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
    }

    public function testGetDecorated(): void
    {
        $pageLoader = $this->createMock(SalesChannelCmsPageLoaderInterface::class);
        $route = new CmsRoute($pageLoader);

        static::expectException(DecorationPatternException::class);
        $route->getDecorated();
    }

    public function testLoadHandlesSlotsAsArray(): void
    {
        $request = new Request([
            'slots' => [
                $this->ids->get('slot-1'),
                $this->ids->get('slot-2'),
                $this->ids->get('slot-3'),
            ],
        ]);

        $expectedCmsPage = new CmsPageEntity();

        $searchResult = $this->getSearchResult($expectedCmsPage);
        $criteria = $this->getExpectedCriteria($request->get('slots'));
        $context = Generator::generateSalesChannelContext();

        $pageLoader = $this->createMock(SalesChannelCmsPageLoaderInterface::class);
        $pageLoader
            ->method('load')
            ->with($request, $criteria, $context)
            ->willReturn($searchResult);

        $route = new CmsRoute($pageLoader);
        $response = $route->load($this->ids->get('cms-page'), $request, $context);

        $actualCmsPage = $response->getCmsPage();
        static::assertSame($expectedCmsPage, $actualCmsPage);
    }

    public function testLoadHandlesSlotsAsString(): void
    {
        $expectedSlots = [
            $this->ids->get('slot-1'),
            $this->ids->get('slot-2'),
            $this->ids->get('slot-3'),
        ];

        $request = new Request([
            'slots' => "{$this->ids->get('slot-1')}|{$this->ids->get('slot-2')}|{$this->ids->get('slot-3')}",
        ]);

        $expectedCmsPage = new CmsPageEntity();

        $searchResult = $this->getSearchResult($expectedCmsPage);
        $criteria = $this->getExpectedCriteria($expectedSlots);
        $context = Generator::generateSalesChannelContext();

        $pageLoader = $this->createMock(SalesChannelCmsPageLoaderInterface::class);
        $pageLoader
            ->method('load')
            ->with($request, $criteria, $context)
            ->willReturn($searchResult);

        $route = new CmsRoute($pageLoader);
        $response = $route->load($this->ids->get('cms-page'), $request, $context);

        $actualCmsPage = $response->getCmsPage();
        static::assertSame($expectedCmsPage, $actualCmsPage);
    }

    public function testLoadCmsPageWithoutProvidedSlots(): void
    {
        $request = new Request([]);
        $expectedCmsPage = new CmsPageEntity();

        $searchResult = $this->getSearchResult($expectedCmsPage);
        $criteria = new Criteria([$this->ids->get('cms-page')]);
        $context = Generator::generateSalesChannelContext();

        $pageLoader = $this->createMock(SalesChannelCmsPageLoaderInterface::class);
        $pageLoader
            ->method('load')
            ->with($request, $criteria, $context)
            ->willReturn($searchResult);

        $route = new CmsRoute($pageLoader);
        $response = $route->load($this->ids->get('cms-page'), $request, $context);

        $actualCmsPage = $response->getCmsPage();
        static::assertSame($expectedCmsPage, $actualCmsPage);
    }

    public function testLoadThrowsExceptionIfNoPageFound(): void
    {
        $request = new Request([]);

        // empty search result
        $searchResult = $this->getSearchResult();

        $criteria = new Criteria([$this->ids->get('cms-page')]);
        $context = Generator::generateSalesChannelContext();

        $pageLoader = $this->createMock(SalesChannelCmsPageLoaderInterface::class);
        $pageLoader
            ->method('load')
            ->with($request, $criteria, $context)
            ->willReturn($searchResult);

        $route = new CmsRoute($pageLoader);

        static::expectException(PageNotFoundException::class);
        $route->load($this->ids->get('cms-page'), $request, $context);
    }

    /**
     * @param array<string> $slots
     */
    private function getExpectedCriteria(array $slots): Criteria
    {
        $criteria = new Criteria([$this->ids->get('cms-page')]);
        $criteria
            ->getAssociation('sections.blocks')
            ->addFilter(new EqualsAnyFilter('slots.id', $slots));

        return $criteria;
    }

    /**
     * @return EntitySearchResult<CmsPageCollection>&MockObject
     */
    private function getSearchResult(?CmsPageEntity $cmsPage = null): EntitySearchResult&MockObject
    {
        $searchResult = $this->createMock(EntitySearchResult::class);

        $searchResult
            ->method('has')
            ->with($this->ids->get('cms-page'))
            ->willReturn((bool) $cmsPage);

        $searchResult
            ->method('first')
            ->willReturn($cmsPage);

        return $searchResult;
    }
}
