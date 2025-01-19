<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Hmac\Guzzle;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\App\AppLocaleProvider;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\App\Hmac\RequestSigner;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Cicada\Tests\Integration\Core\Framework\App\GuzzleTestClientBehaviour;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AuthMiddlewareTest extends TestCase
{
    use GuzzleTestClientBehaviour;

    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $this->resetHistory();
        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    public function testSetDefaultHeaderWithAdminApiSource(): void
    {
        $middleware = new AuthMiddleware('6.4', static::getContainer()->get(AppLocaleProvider::class));
        $request = new Request('POST', 'https://example.local');

        $request = $middleware->getDefaultHeaderRequest($request, [AuthMiddleware::APP_REQUEST_CONTEXT => Context::createDefaultContext()]);

        static::assertArrayHasKey('sw-version', $request->getHeaders());
        static::assertSame('6.4', $request->getHeader('sw-version')[0]);
        static::assertSame(Defaults::LANGUAGE_SYSTEM, $request->getHeader(AuthMiddleware::CICADA_CONTEXT_LANGUAGE)[0]);
        static::assertSame('zh-CN', $request->getHeader(AuthMiddleware::CICADA_USER_LANGUAGE)[0]);
    }

    public function testSetDefaultHeaderWithSaleChannelApiSource(): void
    {
        $middleware = new AuthMiddleware('6.4', static::getContainer()->get(AppLocaleProvider::class));
        $request = new Request('POST', 'https://example.local');

        $request = $middleware->getDefaultHeaderRequest($request, [AuthMiddleware::APP_REQUEST_CONTEXT => $this->salesChannelContext->getContext()]);

        static::assertArrayHasKey('sw-version', $request->getHeaders());
        static::assertSame('6.4', $request->getHeader('sw-version')[0]);
        static::assertSame(Defaults::LANGUAGE_SYSTEM, $request->getHeader(AuthMiddleware::CICADA_CONTEXT_LANGUAGE)[0]);
        static::assertSame('zh-CN', $request->getHeader(AuthMiddleware::CICADA_USER_LANGUAGE)[0]);
    }

    public function testSetDefaultHeaderExist(): void
    {
        $middleware = new AuthMiddleware('6.4', static::getContainer()->get(AppLocaleProvider::class));
        $request = new Request('POST', 'https://example.local', ['sw-version' => '6.5']);

        $request = $middleware->getDefaultHeaderRequest($request, []);

        static::assertArrayHasKey('sw-version', $request->getHeaders());
        static::assertSame('6.5', $request->getHeader('sw-version')[0]);
    }

    public function testCorrectSignRequest(): void
    {
        $optionsRequest
            = [AuthMiddleware::APP_REQUEST_TYPE => [
                AuthMiddleware::APP_SECRET => 'secret',
            ],
                'body' => 'test', ];

        $this->appendNewResponse(new Response(200));

        $client = static::getContainer()->get('cicada.app_system.guzzle');
        $client->post(new Uri('https://example.local'), $optionsRequest);

        $request = $this->getLastRequest();
        static::assertNotNull($request);

        static::assertArrayHasKey(RequestSigner::CICADA_SHOP_SIGNATURE, $request->getHeaders());
    }

    public function testMissingRequiredResponseHeader(): void
    {
        $this->appendNewResponse(new Response(200));

        $client = static::getContainer()->get('cicada.app_system.guzzle');
        $client->post(new Uri('\'https://example.local\''));

        $request = $this->getLastRequest();
        static::assertNotNull($request);

        static::assertArrayNotHasKey(RequestSigner::CICADA_SHOP_SIGNATURE, $request->getHeaders());
    }

    public function testIncorrectInstanceOfOptionRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $optionsRequest = [AuthMiddleware::APP_REQUEST_TYPE => new Response()];
        $this->appendNewResponse(new Response(200));

        $client = static::getContainer()->get('cicada.app_system.guzzle');
        $client->post(new Uri('\'https://example.local\''), $optionsRequest);
    }

    public function testIncorrectAppContextInstanceOfOptionRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $optionsRequest = [AuthMiddleware::APP_REQUEST_CONTEXT => new Response()];
        $this->appendNewResponse(new Response(200));

        $client = static::getContainer()->get('cicada.app_system.guzzle');
        $client->post(new Uri('\'https://example.local\''), $optionsRequest);
    }

    public function testInCorrectAuthenticResponse(): void
    {
        $this->expectException(ServerException::class);

        $optionsRequest
            = [AuthMiddleware::APP_REQUEST_TYPE => [
                AuthMiddleware::APP_SECRET => 'secret',
                AuthMiddleware::VALIDATED_RESPONSE => true,
            ],
                'body' => 'test', ];

        $this->appendNewResponse(new Response(200));

        $client = static::getContainer()->get('cicada.app_system.guzzle');

        $client->post(new Uri('https://example.local'), $optionsRequest);
    }

    public function testOptionRequestArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->appendNewResponse(new Response(200));

        $client = static::getContainer()->get('cicada.app_system.guzzle');

        $optionsRequest
            = [AuthMiddleware::APP_REQUEST_TYPE => 'Not Array',
                'body' => 'test', ];

        $client->post(new Uri('https://example.local'), $optionsRequest);
    }

    public function testOptionRequestMissingSecretArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->appendNewResponse(new Response(200));

        $client = static::getContainer()->get('cicada.app_system.guzzle');

        $optionsRequest
            = [AuthMiddleware::APP_REQUEST_TYPE => [
                AuthMiddleware::VALIDATED_RESPONSE => true,
            ],
                'body' => 'test', ];

        $client->post(new Uri('https://example.local'), $optionsRequest);
    }
}
