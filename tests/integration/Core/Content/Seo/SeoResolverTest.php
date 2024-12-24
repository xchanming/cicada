<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Seo;

use Cicada\Core\Content\Seo\AbstractSeoResolver;
use Cicada\Core\Content\Seo\SeoResolver;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\Seo\StorefrontSalesChannelTestHelper;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
class SeoResolverTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontSalesChannelTestHelper;

    private EntityRepository $seoUrlRepository;

    private AbstractSeoResolver $seoResolver;

    private string $deLanguageId;

    protected function setUp(): void
    {
        $this->seoUrlRepository = static::getContainer()->get('seo_url.repository');
        $this->seoResolver = static::getContainer()->get(SeoResolver::class);

        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM `sales_channel`');

        $this->deLanguageId = $this->getDeDeLanguageId();
    }

    public function testResolveEmpty(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '');
        static::assertEquals(['pathInfo' => '/', 'isCanonical' => false], $resolved);

        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/');
        static::assertEquals(['pathInfo' => '/', 'isCanonical' => false], $resolved);

        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '//');
        static::assertEquals(['pathInfo' => '/', 'isCanonical' => false], $resolved);
    }

    public function testResolveSeoPathPassthrough(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/foo/bar');
        static::assertEquals(['pathInfo' => '/foo/bar', 'isCanonical' => false], $resolved);

        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, 'foo/bar');
        static::assertEquals(['pathInfo' => '/foo/bar', 'isCanonical' => false], $resolved);
    }

    public function testResolveSeoPath(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $this->seoUrlRepository->create([
            [
                'salesChannelId' => $salesChannelId,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/detail/1234',
                'seoPathInfo' => 'awesome-product',
                'isCanonical' => false,
            ],
            [
                'salesChannelId' => $salesChannelId,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/detail/1234',
                'seoPathInfo' => 'awesome-product-v2',
                'isCanonical' => true,
            ],
        ], Context::createDefaultContext());

        // pathInfo
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, 'detail/1234');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(0, $resolved['isCanonical']);
        static::assertArrayHasKey('canonicalPathInfo', $resolved);
        static::assertEquals('/awesome-product-v2', $resolved['canonicalPathInfo']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/detail/1234');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(0, $resolved['isCanonical']);
        static::assertArrayHasKey('canonicalPathInfo', $resolved);
        static::assertEquals('/awesome-product-v2', $resolved['canonicalPathInfo']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, 'detail/1234/');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(0, $resolved['isCanonical']);
        static::assertArrayHasKey('canonicalPathInfo', $resolved);
        static::assertEquals('/awesome-product-v2', $resolved['canonicalPathInfo']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/detail/1234/');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(0, $resolved['isCanonical']);
        static::assertArrayHasKey('canonicalPathInfo', $resolved);
        static::assertEquals('/awesome-product-v2', $resolved['canonicalPathInfo']);

        // old canonical
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, 'awesome-product');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(0, $resolved['isCanonical']);
        static::assertArrayHasKey('canonicalPathInfo', $resolved);
        static::assertEquals('/awesome-product-v2', $resolved['canonicalPathInfo']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/awesome-product');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(0, $resolved['isCanonical']);
        static::assertArrayHasKey('canonicalPathInfo', $resolved);
        static::assertEquals('/awesome-product-v2', $resolved['canonicalPathInfo']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, 'awesome-product/');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(0, $resolved['isCanonical']);
        static::assertArrayHasKey('canonicalPathInfo', $resolved);
        static::assertEquals('/awesome-product-v2', $resolved['canonicalPathInfo']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/awesome-product/');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(0, $resolved['isCanonical']);
        static::assertArrayHasKey('canonicalPathInfo', $resolved);
        static::assertEquals('/awesome-product-v2', $resolved['canonicalPathInfo']);

        // canonical
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, 'awesome-product-v2');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(1, $resolved['isCanonical']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/awesome-product-v2');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(1, $resolved['isCanonical']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, 'awesome-product-v2/');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(1, $resolved['isCanonical']);
        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/awesome-product-v2/');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(1, $resolved['isCanonical']);
    }

    public function testResolveSeoPathWithCanonicalIsNull(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $this->seoUrlRepository->create([
            [
                'salesChannelId' => $salesChannelId,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/detail/1234',
                'seoPathInfo' => 'awesome-product',
                'isCanonical' => null,
            ],
            [
                'salesChannelId' => $salesChannelId,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/detail/1234',
                'seoPathInfo' => 'awesome-product/',
                'isCanonical' => true,
            ],
        ], Context::createDefaultContext());

        $resolved = $this->seoResolver->resolve($context->getLanguageId(), $salesChannelId, '/awesome-product/');
        static::assertEquals('/detail/1234', $resolved['pathInfo']);
        static::assertEquals(1, $resolved['isCanonical']);
    }

    public function testResolveCanonMultiLang(): void
    {
        $salesChannelDeId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext(
            $salesChannelDeId,
            'de',
            $this->deLanguageId,
            [Defaults::LANGUAGE_SYSTEM, $this->deLanguageId]
        );

        $deId = Uuid::randomHex();
        $enId = Uuid::randomHex();

        $this->seoUrlRepository->create([
            [
                'id' => $deId,
                'salesChannelId' => $salesChannelDeId,
                'languageId' => $this->deLanguageId,
                'routeName' => 'r',
                'pathInfo' => '/detail/1234',
                'seoPathInfo' => 'awesome-product-de',
                'isCanonical' => true,
            ],
            [
                'id' => $enId,
                'salesChannelId' => $salesChannelDeId,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/detail/1234',
                'seoPathInfo' => 'awesome-product-en',
                'isCanonical' => true,
            ],
        ], Context::createDefaultContext());

        $actual = $this->seoResolver->resolve($this->deLanguageId, $salesChannelDeId, 'awesome-product-de');
        static::assertArrayHasKey('id', $actual);
        static::assertEquals($deId, Uuid::fromBytesToHex($actual['id']));

        $actual = $this->seoResolver->resolve(Defaults::LANGUAGE_SYSTEM, $salesChannelDeId, 'awesome-product-en');
        static::assertArrayHasKey('id', $actual);
        static::assertEquals($enId, Uuid::fromBytesToHex($actual['id']));
    }

    public function testResolveSamePathForDifferentSalesChannels(): void
    {
        $otherSalesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext(
            $otherSalesChannelId,
            'de',
            $this->deLanguageId,
            [Defaults::LANGUAGE_SYSTEM, $this->deLanguageId]
        );

        $defaultId = Uuid::randomHex();
        $otherId = Uuid::randomHex();

        $this->seoUrlRepository->create([
            [
                'id' => $defaultId,
                'salesChannelId' => null, // default
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/detail/default',
                'seoPathInfo' => 'awesome-product',
                'isCanonical' => true,
            ],
            [
                'id' => $otherId,
                'salesChannelId' => $otherSalesChannelId,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/detail/other',
                'seoPathInfo' => 'awesome-product',
                'isCanonical' => true,
            ],
        ], Context::createDefaultContext());

        $unknownSalesChannelId = Uuid::randomHex();
        // returns default for unknown sales channels
        $actual = $this->seoResolver->resolve(Defaults::LANGUAGE_SYSTEM, $unknownSalesChannelId, 'awesome-product');
        static::assertEquals('/detail/default', $actual['pathInfo']);
        static::assertTrue((bool) $actual['isCanonical']);

        $actual = $this->seoResolver->resolve(Defaults::LANGUAGE_SYSTEM, $otherSalesChannelId, 'awesome-product');
        static::assertEquals('/detail/other', $actual['pathInfo']);
        static::assertTrue((bool) $actual['isCanonical']);
    }

    public function testSalesChannelSpecificSeoulWillBePrioritized(): void
    {
        $salesChannelId = Uuid::randomHex();
        $this->createStorefrontSalesChannelContext($salesChannelId, 'test');

        $this->seoUrlRepository->create([
            [
                'salesChannelId' => null, // default
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/default',
                'seoPathInfo' => 'awesome-product',
                'isCanonical' => true,
            ],
            [
                'salesChannelId' => $salesChannelId,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'routeName' => 'r',
                'pathInfo' => '/sales-channel',
                'seoPathInfo' => 'awesome-product',
                'isCanonical' => true,
            ],
        ], Context::createDefaultContext());

        $salesChannelResponse = $this->seoResolver->resolve(Defaults::LANGUAGE_SYSTEM, $salesChannelId, 'awesome-product');
        static::assertSame('/sales-channel', $salesChannelResponse['pathInfo']);

        $salesChannelResponse = $this->seoResolver->resolve(Defaults::LANGUAGE_SYSTEM, Uuid::randomHex(), 'awesome-product');
        static::assertSame('/default', $salesChannelResponse['pathInfo']);
    }
}
