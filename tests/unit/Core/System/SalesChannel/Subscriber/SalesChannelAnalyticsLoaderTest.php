<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelAnalytics\SalesChannelAnalyticsCollection;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelAnalytics\SalesChannelAnalyticsEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\Subscriber\SalesChannelAnalyticsLoader;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(SalesChannelAnalyticsLoader::class)]
class SalesChannelAnalyticsLoaderTest extends TestCase
{
    public function testSalesChannelDoesNotHaveAnalytics(): void
    {
        $event = $this->getEvent(Generator::createSalesChannelContext());
        $repository = new StaticEntityRepository([]);

        $loader = new SalesChannelAnalyticsLoader($repository);
        $loader->loadAnalytics($event);

        static::assertArrayNotHasKey('storefrontAnalytics', $event->getParameters());
    }

    public function testSalesChannelHasAnalytics(): void
    {
        $analyticsId = Uuid::randomHex();
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setAnalyticsId($analyticsId);
        $event = $this->getEvent($salesChannelContext);
        $analytics = new SalesChannelAnalyticsEntity();
        $analytics->setId($analyticsId);
        $repository = new StaticEntityRepository([new SalesChannelAnalyticsCollection([$analytics])]);

        $loader = new SalesChannelAnalyticsLoader($repository);
        $loader->loadAnalytics($event);

        static::assertArrayHasKey('storefrontAnalytics', $event->getParameters());
        static::assertInstanceOf(SalesChannelAnalyticsEntity::class, $event->getParameters()['storefrontAnalytics']);
    }

    public function testSalesChannelAnalyticsNotFound(): void
    {
        $analyticsId = Uuid::randomHex();
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setAnalyticsId($analyticsId);
        $event = $this->getEvent($salesChannelContext);
        $repository = new StaticEntityRepository([new SalesChannelAnalyticsCollection([])]);

        $loader = new SalesChannelAnalyticsLoader($repository);
        $loader->loadAnalytics($event);

        static::assertArrayHasKey('storefrontAnalytics', $event->getParameters());
        static::assertNull($event->getParameters()['storefrontAnalytics']);
    }

    private function getEvent(SalesChannelContext $salesChannelContext): StorefrontRenderEvent
    {
        return new StorefrontRenderEvent(
            'test.html.twig',
            [],
            new Request(),
            $salesChannelContext,
        );
    }
}
