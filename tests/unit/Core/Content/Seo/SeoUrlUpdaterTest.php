<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Seo;

use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Seo\SeoUrlGenerator;
use Cicada\Core\Content\Seo\SeoUrlPersister;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteRegistry;
use Cicada\Core\Content\Seo\SeoUrlUpdater;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Storefront\Framework\Seo\SeoUrlRoute\ProductPageSeoUrlRoute;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('inventory')]
#[CoversClass(SeoUrlUpdater::class)]
class SeoUrlUpdaterTest extends TestCase
{
    /**
     * @var StaticEntityRepository<LanguageCollection>
     */
    private StaticEntityRepository $languageRepository;

    private SeoUrlRouteRegistry $seoUrlRouteRegistry;

    private SeoUrlGenerator&MockObject $seoUrlGenerator;

    private SeoUrlPersister&MockObject $seoUrlPersister;

    private Connection&MockObject $connection;

    /**
     * @var StaticEntityRepository<SalesChannelCollection>
     */
    private StaticEntityRepository $salesChannelRepository;

    protected function setUp(): void
    {
        $this->seoUrlGenerator = $this->createMock(SeoUrlGenerator::class);
        $this->seoUrlPersister = $this->createMock(SeoUrlPersister::class);
        $this->connection = $this->createMock(Connection::class);
    }

    public function testUpdateWithoutDomain(): void
    {
        $seoUrlUpdater = $this->createSeoUrlUpdater();

        $this->connection->method('fetchAllAssociative')->willReturn([]);
        $this->seoUrlPersister->expects(static::never())->method('updateSeoUrls');

        $seoUrlUpdater->update('test', []);
    }

    public function testUpdateWithoutDefaultTemplates(): void
    {
        $seoUrlUpdater = $this->createSeoUrlUpdater();

        $this->connection->method('fetchAllAssociative')->willReturn([
            [
                'salesChannelId' => Uuid::randomHex(),
                'languageId' => Uuid::randomHex(),
            ],
        ]);
        $this->connection->method('fetchAllKeyValue')->willReturn([]);

        $this->seoUrlPersister->expects(static::never())->method('updateSeoUrls');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Default templates not configured');
        $seoUrlUpdater->update('test', []);
    }

    public function testUpdateWithoutRoute(): void
    {
        $seoUrlUpdater = $this->createSeoUrlUpdater();

        $this->connection->method('fetchAllAssociative')->willReturn([
            [
                'salesChannelId' => Uuid::randomHex(),
                'languageId' => Uuid::randomHex(),
            ],
        ]);

        $this->connection->method('fetchAllKeyValue')->willReturn(
            [
                '' => '{{ product.translated.name }}/{{ product.productNumber }}',
            ]
        );

        $this->seoUrlPersister->expects(static::never())->method('updateSeoUrls');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Route by name test not found');

        $seoUrlUpdater->update('test', []);
    }

    public function testUpdateWithOutSalesChannel(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([
            [
                'salesChannelId' => Uuid::randomHex(),
                'languageId' => Uuid::randomHex(),
            ],
        ]);

        $this->connection->method('fetchAllKeyValue')->willReturn(
            [
                '' => '{{ product.translated.name }}/{{ product.productNumber }}',
            ]
        );

        $seoUrlUpdater = $this->createSeoUrlUpdater(
            [
                new LanguageCollection([]),
            ],
            [
                new SalesChannelCollection([]),
            ],
            [
                new ProductPageSeoUrlRoute(new ProductDefinition()),
            ]
        );

        $this->seoUrlPersister->expects(static::never())->method('updateSeoUrls');

        $seoUrlUpdater->update('frontend.detail.page', []);
    }

    public function testUpdateGetPersisted(): void
    {
        $this->connection->method('fetchAllAssociative')->willReturn([
            [
                'salesChannelId' => 'testSalsesChannelId',
                'languageId' => 'testLanguageId',
            ],
        ]);

        $this->connection->method('fetchAllKeyValue')->willReturn(
            [
                '' => '{{ product.translated.name }}/{{ product.productNumber }}',
            ]
        );

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId('testSalsesChannelId');

        $language = new LanguageEntity();
        $language->setId('testLanguageId');

        $seoUrlUpdater = $this->createSeoUrlUpdater(
            [
                new LanguageCollection([
                    $language,
                ]),
            ],
            [
                new SalesChannelCollection([
                    $salesChannel,
                ]),
            ],
            [
                new ProductPageSeoUrlRoute(new ProductDefinition()),
            ]
        );

        $this->seoUrlGenerator->expects(static::once())->method('generate');
        $this->seoUrlPersister->expects(static::once())->method('updateSeoUrls');

        $seoUrlUpdater->update('frontend.detail.page', []);
    }

    /**
     * @param LanguageCollection[] $languageSearches
     * @param SalesChannelCollection[] $salesChannelSearches
     * @param SeoUrlRouteInterface[] $seoUrlRoutes
     */
    private function createSeoUrlUpdater(
        array $languageSearches = [],
        array $salesChannelSearches = [],
        array $seoUrlRoutes = []
    ): SeoUrlUpdater {
        $this->languageRepository = new StaticEntityRepository($languageSearches);
        $this->seoUrlRouteRegistry = new SeoUrlRouteRegistry($seoUrlRoutes);
        $this->salesChannelRepository = new StaticEntityRepository($salesChannelSearches);

        return new SeoUrlUpdater(
            $this->languageRepository,
            $this->seoUrlRouteRegistry,
            $this->seoUrlGenerator,
            $this->seoUrlPersister,
            $this->connection,
            $this->salesChannelRepository
        );
    }
}
