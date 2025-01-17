<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Cache;

use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Adapter\Cache\Http\CacheStore;
use Cicada\Core\Framework\Adapter\Cache\Http\HttpCacheKeyGenerator;
use Cicada\Core\Framework\Adapter\Kernel\HttpCacheKernel;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Routing\RequestTransformerInterface;
use Cicada\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Group('skip-paratest')]
#[Group('cache')]
class HttpCacheIntegrationTest extends TestCase
{
    use AppSystemTestBehaviour;
    use CacheTestBehaviour;
    use KernelTestBehaviour;

    private static string $originalHttpCacheValue;

    public static function setUpBeforeClass(): void
    {
        self::$originalHttpCacheValue = $_SERVER['CICADA_HTTP_CACHE_ENABLED'] ?? '';
    }

    protected function setUp(): void
    {
        $_ENV['CICADA_HTTP_CACHE_ENABLED'] = $_SERVER['CICADA_HTTP_CACHE_ENABLED'] = '1';

        KernelLifecycleManager::bootKernel();

        static::getContainer()
            ->get(Connection::class)
            ->beginTransaction();
    }

    protected function tearDown(): void
    {
        $_ENV['CICADA_HTTP_CACHE_ENABLED'] = $_SERVER['CICADA_HTTP_CACHE_ENABLED'] = self::$originalHttpCacheValue;

        $connection = static::getContainer()->get(Connection::class);

        static::assertEquals(
            1,
            $connection->getTransactionNestingLevel(),
            'Too many Nesting Levels.
            Probably one transaction was not closed properly.
            This may affect following Tests in an unpredictable manner!
            Current nesting level: "' . $connection->getTransactionNestingLevel() . '".'
        );

        $connection->rollBack();
    }

    public function testCacheHit(): void
    {
        $kernel = $this->getCacheKernel();

        $appUrl = EnvironmentHelper::getVariable('APP_URL');
        static::assertIsString($appUrl);

        $request = $this->createRequest($appUrl);

        $response = $kernel->handle($request);
        static::assertTrue($response->headers->has('x-symfony-cache'));
        static::assertEquals('GET /: miss, store', $response->headers->get('x-symfony-cache'));

        $response = $kernel->handle($request);
        static::assertEquals('GET /: fresh', $response->headers->get('x-symfony-cache'));
    }

    public function testCacheHitWithDifferentCacheKeys(): void
    {
        $kernel = $this->getCacheKernel();

        $appUrl = EnvironmentHelper::getVariable('APP_URL');
        static::assertIsString($appUrl);

        $request = $this->createRequest($appUrl);
        $request->cookies->set(HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE, 'a');

        $response = $kernel->handle($request);
        static::assertEquals('GET /: miss, store', $response->headers->get('x-symfony-cache'));

        $response = $kernel->handle($request);
        static::assertEquals('GET /: fresh', $response->headers->get('x-symfony-cache'));

        $request->cookies->set(HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE, 'b');

        $response = $kernel->handle($request);
        static::assertEquals('GET /: miss, store', $response->headers->get('x-symfony-cache'));
    }

    public function testCacheForAppScriptEndpointIsEnabledByDefault(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/http-cache-cases');

        $kernel = $this->getCacheKernel();

        $route = '/storefront/script/cache-default';
        $request = $this->createRequest(EnvironmentHelper::getVariable('APP_URL') . $route);

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: miss, store', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: fresh', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));
    }

    public function testCacheForAppScriptEndpointOptOut(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/http-cache-cases');

        $kernel = $this->getCacheKernel();

        $route = '/storefront/script/cache-disable';
        $request = $this->createRequest(EnvironmentHelper::getVariable('APP_URL') . $route);

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: miss', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: miss', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));
    }

    public function testCacheForAppScriptEndpointCustomCacheTags(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/http-cache-cases');

        $kernel = $this->getCacheKernel();

        $route = '/storefront/script/custom-cache-tags';
        $request = $this->createRequest(EnvironmentHelper::getVariable('APP_URL') . $route);

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: miss, store', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: fresh', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));

        $cacheInvalidator = static::getContainer()->get(CacheInvalidator::class);
        $cacheInvalidator->invalidate(['my-custom-tag'], true);

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: miss, store', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));
    }

    public function testCacheForAppScriptEndpointCustomCacheTagsWithScriptInvalidation(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/http-cache-cases');

        $kernel = $this->getCacheKernel();

        $route = '/storefront/script/custom-cache-tags';
        $request = $this->createRequest(EnvironmentHelper::getVariable('APP_URL') . $route);

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: miss, store', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: fresh', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));

        $ids = new IdsCollection();
        $productRepo = static::getContainer()->get('product.repository');
        // entity written event will execute the cache invalidation script, which will invalidate our custom tag
        $productRepo->create([
            (new ProductBuilder($ids, 'p1'))
            ->price(100)
            ->build(),
        ], Context::createDefaultContext());

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: miss, store', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));
    }

    public function testCacheForAppScriptEndpointCustomCacheConfig(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/_fixtures/http-cache-cases');

        $kernel = $this->getCacheKernel();

        $route = '/storefront/script/custom-cache-config';
        $request = $this->createRequest(EnvironmentHelper::getVariable('APP_URL') . $route);

        $this->addEventListener(static::getContainer()->get('event_dispatcher'), KernelEvents::RESPONSE, function (ResponseEvent $event): void {
            static::assertEquals(5, $event->getResponse()->getMaxAge());
            static::assertEquals('logged-in', $event->getResponse()->headers->get(HttpCacheKeyGenerator::INVALIDATION_STATES_HEADER));
        }, -1501);

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: miss, store', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));

        $response = $kernel->handle($request);
        static::assertEquals(\sprintf('GET %s: fresh', $route), $response->headers->get('x-symfony-cache'));
        static::assertFalse($response->headers->has(CacheStore::TAG_HEADER));
    }

    private function createRequest(?string $url = null): Request
    {
        if ($url === null) {
            $url = static::getContainer()->get(Connection::class)->fetchOne('SELECT url FROM sales_channel_domain LIMIT 1');
        }

        $request = Request::create($url);

        // resolves seo urls and detects storefront sales channels
        return static::getContainer()
            ->get(RequestTransformerInterface::class)
            ->transform($request);
    }

    private function getCacheKernel(): HttpCacheKernel
    {
        return static::getContainer()->get('http_kernel.cache');
    }
}
