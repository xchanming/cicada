<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\Controller;

use Cicada\Core\Framework\Api\Exception\MissingPrivilegeException;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\MessageQueue\FullEntityIndexerMessage;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\TraceableMessageBus;

/**
 * @internal
 */
#[Package('fundamentals@framework')]
#[Group('skip-paratest')]
class CacheControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    private TagAwareAdapterInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = static::getContainer()->get('cache.object');
    }

    #[Group('slow')]
    public function testClearCacheEndpoint(): void
    {
        $this->cache = static::getContainer()->get('cache.object');

        $item = $this->cache->getItem('foo');
        $item->set('bar');
        $item->tag(['foo-tag']);
        $this->cache->save($item);

        $item = $this->cache->getItem('bar');
        $item->set('foo');
        $item->tag(['bar-tag']);
        $this->cache->save($item);

        static::assertTrue($this->cache->getItem('foo')->isHit());
        static::assertTrue($this->cache->getItem('bar')->isHit());

        $this->getBrowser()->request('DELETE', '/api/_action/cache');

        /** @var JsonResponse $response */
        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), print_r($response->getContent(), true));

        static::assertFalse($this->cache->getItem('foo')->isHit());
        static::assertFalse($this->cache->getItem('bar')->isHit());
    }

    public function testCacheInfoEndpoint(): void
    {
        $this->getBrowser()->request('GET', '/api/_action/cache_info');

        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode(), print_r($response->getContent(), true));
        $decodedContent = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('environment', $decodedContent);
        static::assertSame('test', $decodedContent['environment']);
        static::assertArrayHasKey('httpCache', $decodedContent);
        static::assertIsBool($decodedContent['httpCache']);
        static::assertArrayHasKey('cacheAdapter', $decodedContent);
        static::assertSame('CacheDecorator', $decodedContent['cacheAdapter']);
    }

    public function testCacheIndexEndpoint(): void
    {
        $this->getBrowser()->request('POST', '/api/_action/index');

        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), print_r($response->getContent(), true));
    }

    public function testCacheIndexEndpointWithSkipParameter(): void
    {
        /** @var TraceableMessageBus $bus */
        $bus = static::getContainer()->get('messenger.bus.cicada');
        $bus->reset();

        $this->getBrowser()->request(
            'POST',
            '/api/_action/index',
            [],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['skip' => ['category.indexer']], \JSON_THROW_ON_ERROR)
        );

        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), print_r($response->getContent(), true));

        $messages = $bus->getDispatchedMessages();

        static::assertCount(1, $messages, 'Expected exactly one dispatched message');

        $message = $messages[0]['message'];
        static::assertInstanceOf(FullEntityIndexerMessage::class, $message);

        static::assertContains('category.indexer', $message->getSkip());
    }

    public function testCacheIndexEndpointWithOnlyParameter(): void
    {
        /** @var TraceableMessageBus $bus */
        $bus = static::getContainer()->get('messenger.bus.cicada');
        $bus->reset();

        $this->getBrowser()->request(
            'POST',
            '/api/_action/index',
            [],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['only' => ['category.indexer']], \JSON_THROW_ON_ERROR)
        );

        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), print_r($response->getContent(), true));

        $messages = $bus->getDispatchedMessages();

        static::assertCount(1, $messages, 'Expected exactly one dispatched message');

        $message = $messages[0]['message'];
        static::assertInstanceOf(FullEntityIndexerMessage::class, $message);

        static::assertContains('category.indexer', $message->getOnly());
    }

    public function testCacheIndexEndpointNoPermissions(): void
    {
        try {
            $this->authorizeBrowser($this->getBrowser(), [], ['something']);
            $this->getBrowser()->request('POST', '/api/_action/index');

            $response = $this->getBrowser()->getResponse();

            static::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode(), (string) $response->getContent());
            $decode = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
            static::assertEquals(MissingPrivilegeException::MISSING_PRIVILEGE_ERROR, $decode['errors'][0]['code'], (string) $response->getContent());
        } finally {
            $this->resetBrowser();
        }
    }
}
