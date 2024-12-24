<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Seo\SeoUrlRoute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Seo\SeoUrlGenerator;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Framework\Seo\SeoUrlRoute\ProductPageSeoUrlRoute;

/**
 * @internal
 */
#[CoversClass(ProductPageSeoUrlRoute::class)]
class ProductPageSeoUrlRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    public function testMainCategories(): void
    {
        $ids = new IdsCollection();

        $salesChannel = $this->createSalesChannel();

        $product = (new ProductBuilder($ids, 'p1'))
            ->price(100)
            ->visibility()
            ->visibility($salesChannel['id'])
            ->categories(['c1', 'c2'])
            ->mainCategory(TestDefaults::SALES_CHANNEL, 'c1')
            ->mainCategory($salesChannel['id'], 'c2')
            ->build();

        static::getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        $this->generateAndAssert(
            ids: $ids->getList(['p1']),
            template: '{{ product.mainCategories.first.category.translated.name }}',
            salesChannelId: TestDefaults::SALES_CHANNEL,
            expected: ['c1']
        );

        $this->generateAndAssert(
            ids: $ids->getList(['p1']),
            template: '{{ product.mainCategories.first.category.translated.name }}',
            salesChannelId: $salesChannel['id'],
            expected: ['c2']
        );
    }

    /**
     * @param array<string> $ids
     * @param array<string> $expected
     */
    private function generateAndAssert(array $ids, string $template, string $salesChannelId, array $expected): void
    {
        $context = Context::createDefaultContext();

        $channels = static::getContainer()
            ->get('sales_channel.repository')
            ->search(new Criteria([$salesChannelId]), $context);

        $channel = $channels->get($salesChannelId);

        static::assertInstanceOf(SalesChannelEntity::class, $channel);

        $generator = static::getContainer()->get(SeoUrlGenerator::class);

        $urls = $generator->generate(
            ids: $ids,
            template: $template,
            route: static::getContainer()->get(ProductPageSeoUrlRoute::class),
            context: $context,
            salesChannel: $channel
        );

        $urls = iterator_to_array($urls);
        static::assertCount(\count($expected), $urls);

        foreach ($urls as $url) {
            static::assertContains($url->getSeoPathInfo(), $expected);
        }
    }
}
