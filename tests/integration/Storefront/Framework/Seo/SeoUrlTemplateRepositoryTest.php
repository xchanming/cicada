<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Seo;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateDefinition;
use Cicada\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Framework\Seo\SeoUrlRoute\ProductPageSeoUrlRoute;

/**
 * @internal
 */
#[Package('buyers-experience')]
class SeoUrlTemplateRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testCreate(): void
    {
        $id = Uuid::randomHex();
        $template = [
            'id' => $id,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'routeName' => ProductPageSeoUrlRoute::ROUTE_NAME,
            'entityName' => static::getContainer()->get(ProductDefinition::class)->getEntityName(),
            'template' => ProductPageSeoUrlRoute::DEFAULT_TEMPLATE,
        ];

        $context = Context::createDefaultContext();
        /** @var EntityRepository $repo */
        $repo = static::getContainer()->get('seo_url_template.repository');
        $events = $repo->create([$template], $context);
        static::assertNotNull($events->getEvents());
        static::assertCount(1, $events->getEvents());

        $event = $events->getEventByEntityName(SeoUrlTemplateDefinition::ENTITY_NAME);
        static::assertNotNull($event);
        static::assertCount(1, $event->getPayloads());
    }

    /**
     * @param array<string, string> $template
     */
    #[DataProvider('templateUpdateDataProvider')]
    public function testUpdate(string $id, array $template): void
    {
        $context = Context::createDefaultContext();
        /** @var EntityRepository $repo */
        $repo = static::getContainer()->get('seo_url_template.repository');
        $repo->create([$template], $context);

        $update = [
            'id' => $id,
            'routeName' => 'foo_bar',
        ];
        $events = $repo->update([$update], $context);
        $event = $events->getEventByEntityName(SeoUrlTemplateDefinition::ENTITY_NAME);
        static::assertNotNull($event);
        static::assertCount(1, $event->getPayloads());

        /** @var SeoUrlTemplateEntity $first */
        $first = $repo->search(new Criteria([$id]), $context)->first();
        static::assertEquals($update['id'], $first->getId());
        static::assertEquals($update['routeName'], $first->getRouteName());
    }

    public function testDelete(): void
    {
        $id = Uuid::randomHex();
        $template = [
            'id' => $id,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'routeName' => ProductPageSeoUrlRoute::ROUTE_NAME,
            'entityName' => static::getContainer()->get(ProductDefinition::class)->getEntityName(),
            'template' => ProductPageSeoUrlRoute::DEFAULT_TEMPLATE,
        ];

        $context = Context::createDefaultContext();
        /** @var EntityRepository $repo */
        $repo = static::getContainer()->get('seo_url_template.repository');
        $repo->create([$template], $context);

        $result = $repo->delete([['id' => $id]], $context);
        $event = $result->getEventByEntityName(SeoUrlTemplateDefinition::ENTITY_NAME);
        static::assertNotNull($event);
        static::assertEquals([$id], $event->getIds());

        /** @var SeoUrlTemplateEntity|null $first */
        $first = $repo->search(new Criteria([$id]), $context)->first();
        static::assertNull($first);
    }

    public static function templateUpdateDataProvider(): \Generator
    {
        $templates = [
            [
                'id' => null,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'routeName' => ProductPageSeoUrlRoute::ROUTE_NAME,
                'entityName' => ProductDefinition::ENTITY_NAME,
                'template' => ProductPageSeoUrlRoute::DEFAULT_TEMPLATE,
            ],
            [
                'id' => null,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'routeName' => ProductPageSeoUrlRoute::ROUTE_NAME,
                'entityName' => ProductDefinition::ENTITY_NAME,
                'template' => '',
            ],
        ];

        foreach ($templates as $template) {
            $id = Uuid::randomHex();
            $template['id'] = $id;

            yield [
                'id' => $id,
                'template' => $template,
            ];
        }
    }
}
