<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Pagelet\Header;

use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Category\Service\NavigationLoaderInterface;
use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Content\Category\Tree\TreeItem;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\Currency\SalesChannel\AbstractCurrencyRoute;
use Cicada\Core\System\Currency\SalesChannel\CurrencyRouteResponse;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\Language\SalesChannel\AbstractLanguageRoute;
use Cicada\Core\System\Language\SalesChannel\LanguageRouteResponse;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Pagelet\Header\HeaderPageletLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(HeaderPageletLoader::class)]
class HeaderPageletLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $salesChannelContext = Generator::generateSalesChannelContext();

        $currencyRoute = $this->createMock(AbstractCurrencyRoute::class);
        $currencyRoute->method('load')->willReturn(new CurrencyRouteResponse(new CurrencyCollection([
            (new CurrencyEntity())->assign(['id' => $salesChannelContext->getCurrencyId()]),
        ])));

        $languageRoute = $this->createMock(AbstractLanguageRoute::class);
        $languageRoute->method('load')->willReturn(new LanguageRouteResponse(new EntitySearchResult(
            LanguageDefinition::ENTITY_NAME,
            1,
            new LanguageCollection([
                (new LanguageEntity())->assign(['id' => $salesChannelContext->getLanguageId()]),
            ]),
            null,
            new Criteria(),
            $salesChannelContext->getContext(),
        )));

        $navigationLoader = $this->createMock(NavigationLoaderInterface::class);
        $categoryId1 = Uuid::randomHex();
        $categoryId2 = Uuid::randomHex();
        $category1 = (new CategoryEntity())->assign(['id' => $categoryId1]);
        $category2 = (new CategoryEntity())->assign(['id' => $categoryId2]);
        $navigationCategoryId = $salesChannelContext->getSalesChannel()->getNavigationCategoryId();
        $navigationLoader->method('load')->willReturnMap(
            [
                [
                    $navigationCategoryId,
                    $salesChannelContext,
                    $navigationCategoryId,
                    $salesChannelContext->getSalesChannel()->getNavigationCategoryDepth(),
                    new Tree($category2, [new TreeItem($category1, []), new TreeItem($category2, [])]),
                ],
            ]
        );

        $headerPageletLoader = new HeaderPageletLoader($eventDispatcher, $currencyRoute, $languageRoute, $navigationLoader);
        $header = $headerPageletLoader->load(new Request(), $salesChannelContext);

        static::assertSame($salesChannelContext->getLanguageId(), $header->getActiveLanguage()->getId());
        static::assertSame($salesChannelContext->getCurrencyId(), $header->getActiveCurrency()->getId());

        $navigation = $header->getNavigation();
        static::assertNotNull($navigation);
        $tree = $navigation->getTree();
        static::assertCount(2, $tree);
        static::assertSame($categoryId1, $tree[0]->getCategory()->getId());
        static::assertSame($categoryId2, $tree[1]->getCategory()->getId());
    }
}
