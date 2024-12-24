<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Seo\SeoUrl;

use Cicada\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Cicada\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\Seo\StorefrontSalesChannelTestHelper;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class StorefrontSeoUrlRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontSalesChannelTestHelper;

    /**
     * @var EntityRepository<SeoUrlCollection>
     */
    private EntityRepository $seoUrlRepository;

    private SalesChannelRepository $salesChannelSeoUrlRepository;

    protected function setUp(): void
    {
        $this->seoUrlRepository = static::getContainer()->get('seo_url.repository');
        $this->salesChannelSeoUrlRepository = static::getContainer()->get('sales_channel.seo_url.repository');
    }

    public function testOnlyCanonical(): void
    {
        $canonicalId = Uuid::randomHex();
        $oldId = Uuid::randomHex();
        $foreignKey = Uuid::randomHex();

        $salesChannelContext = $this->createStorefrontSalesChannelContext(Uuid::randomHex(), 'test');

        $this->seoUrlRepository->create(
            [
                [
                    'id' => $canonicalId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => null,
                    'routeName' => 'test',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/canonical',
                    'isCanonical' => true,
                ],
                [
                    'id' => $oldId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => null,
                    'routeName' => 'test',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/old',
                    'isCanonical' => false,
                ],
            ],
            $salesChannelContext->getContext()
        );

        $criteria = new Criteria([$canonicalId, $oldId]);

        $seoUrls = $this->salesChannelSeoUrlRepository->search($criteria, $salesChannelContext)->getEntities();
        static::assertCount(1, $seoUrls);
        $first = $seoUrls->first();
        static::assertInstanceOf(SeoUrlEntity::class, $first);
        static::assertSame($canonicalId, $first->getId());
    }

    public function testContextLanguage(): void
    {
        $deLanguageId = Uuid::randomHex();
        $deId = Uuid::randomHex();
        $enId = Uuid::randomHex();
        $foreignKey = Uuid::randomHex();

        $this->upsertLanguage($deLanguageId, 'test de');
        $salesChannelContext = $this->createStorefrontSalesChannelContext(Uuid::randomHex(), 'test', $deLanguageId);

        $this->seoUrlRepository->create(
            [
                [
                    'id' => $deId,
                    'languageId' => $deLanguageId,
                    'salesChannelId' => null,
                    'routeName' => 'test',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/de',
                    'isCanonical' => true,
                ],
                [
                    'id' => $enId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => null,
                    'routeName' => 'test',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/en',
                    'isCanonical' => true,
                ],
            ],
            $salesChannelContext->getContext()
        );

        $criteria = new Criteria([$deId, $enId]);

        $seoUrls = $this->salesChannelSeoUrlRepository->search($criteria, $salesChannelContext)->getEntities();
        static::assertCount(1, $seoUrls);
        $first = $seoUrls->first();
        static::assertInstanceOf(SeoUrlEntity::class, $first);
        static::assertSame($deId, $first->getId());
    }

    public function testContextSalesChannel(): void
    {
        $expectedId = Uuid::randomHex();
        $otherId = Uuid::randomHex();
        $salesChannelId = Uuid::randomHex();
        $otherSalesChannelId = Uuid::randomHex();
        $foreignKey = Uuid::randomHex();

        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');
        $this->createStorefrontSalesChannelContext($otherSalesChannelId, 'other');

        $this->seoUrlRepository->create(
            [
                [
                    'id' => $expectedId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => $salesChannelId,
                    'routeName' => 'test',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/expected',
                    'isCanonical' => true,
                ],
                [
                    'id' => $otherId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => $otherSalesChannelId,
                    'routeName' => 'test',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/other',
                    'isCanonical' => true,
                ],
            ],
            $salesChannelContext->getContext()
        );

        $criteria = new Criteria([$expectedId, $otherId]);

        $seoUrls = $this->salesChannelSeoUrlRepository->search($criteria, $salesChannelContext)->getEntities();
        static::assertCount(1, $seoUrls);
        $first = $seoUrls->first();
        static::assertInstanceOf(SeoUrlEntity::class, $first);
        static::assertSame($expectedId, $first->getId());
    }

    public function testSalesChannelFallback(): void
    {
        $expectedId = Uuid::randomHex();
        $otherId = Uuid::randomHex();
        $salesChannelId = Uuid::randomHex();
        $otherSalesChannelId = Uuid::randomHex();
        $foreignKey = Uuid::randomHex();

        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');
        $this->createStorefrontSalesChannelContext($otherSalesChannelId, 'other');

        $this->seoUrlRepository->create(
            [
                [
                    'id' => $expectedId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => null,
                    'routeName' => 'fallback',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/expected',
                    'isCanonical' => true,
                ],
                [
                    'id' => $otherId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => $otherSalesChannelId,
                    'routeName' => 'test',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/other',
                    'isCanonical' => true,
                ],
            ],
            $salesChannelContext->getContext()
        );

        $criteria = new Criteria([$expectedId, $otherId]);

        $seoUrls = $this->salesChannelSeoUrlRepository->search($criteria, $salesChannelContext)->getEntities();
        static::assertCount(1, $seoUrls);
        $first = $seoUrls->first();
        static::assertInstanceOf(SeoUrlEntity::class, $first);
        static::assertSame($expectedId, $first->getId());
    }

    public function testMatchingAndFallbackSalesChannel(): void
    {
        $expectedId = Uuid::randomHex();
        $expectedFallbackId = Uuid::randomHex();
        $otherId = Uuid::randomHex();
        $salesChannelId = Uuid::randomHex();
        $otherSalesChannelId = Uuid::randomHex();
        $foreignKey = Uuid::randomHex();

        $salesChannelContext = $this->createStorefrontSalesChannelContext($salesChannelId, 'test');
        $this->createStorefrontSalesChannelContext($otherSalesChannelId, 'other');

        $this->seoUrlRepository->create(
            [
                [
                    'id' => $expectedFallbackId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => null,
                    'routeName' => 'fallback',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/expected',
                    'isCanonical' => true,
                ],
                [
                    'id' => $expectedId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => $salesChannelId,
                    'routeName' => 'fallback',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/expected',
                    'isCanonical' => true,
                ],
                [
                    'id' => $otherId,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'salesChannelId' => $otherSalesChannelId,
                    'routeName' => 'test',
                    'foreignKey' => $foreignKey,
                    'pathInfo' => '/detail/1234',
                    'seoPathInfo' => '/other',
                    'isCanonical' => true,
                ],
            ],
            $salesChannelContext->getContext()
        );

        $criteria = new Criteria([$expectedId, $expectedFallbackId, $otherId]);

        $seoUrls = $this->salesChannelSeoUrlRepository->search($criteria, $salesChannelContext)->getEntities();
        static::assertCount(2, $seoUrls);

        $expected = $seoUrls->get($expectedId);
        static::assertInstanceOf(SeoUrlEntity::class, $expected);
        static::assertSame($salesChannelId, $expected->getSalesChannelId());

        $expectedFallback = $seoUrls->get($expectedFallbackId);
        static::assertInstanceOf(SeoUrlEntity::class, $expectedFallback);
        static::assertNull($expectedFallback->getSalesChannelId());
    }

    private function upsertLanguage(string $id, string $name): void
    {
        $languageRepo = static::getContainer()->get('language.repository');
        $languageRepo->upsert([[
            'id' => $id,
            'name' => $name,
            'locale' => [
                'id' => $id,
                'code' => 'X-' . $name,
                'name' => 'test',
                'territory' => $name . ' territory',
            ],
            'translationCodeId' => $id,
        ]], Context::createDefaultContext());
    }
}
