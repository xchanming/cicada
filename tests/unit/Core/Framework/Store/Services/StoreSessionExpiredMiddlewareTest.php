<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Services;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Exception\StoreSessionExpiredException;
use Cicada\Core\Framework\Store\Services\StoreSessionExpiredMiddleware;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\User\UserEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(StoreSessionExpiredMiddleware::class)]
class StoreSessionExpiredMiddlewareTest extends TestCase
{
    public function testReturnsResponseIfStatusCodeIsNotUnauthorized(): void
    {
        $response = new Response(200, [], '{"payload":"data"}');

        $middleware = new StoreSessionExpiredMiddleware(
            $this->createMock(Connection::class),
            new RequestStack(),
        );

        $handledResponse = $middleware($response);

        static::assertSame($response, $handledResponse);
    }

    public function testReturnsResponseWithRewoundBodyIfCodeIsNotMatched(): void
    {
        $response = new Response(401, [], '{"payload":"data"}');

        $middleware = new StoreSessionExpiredMiddleware(
            $this->createMock(Connection::class),
            new RequestStack(),
        );

        $handledResponse = $middleware($response);

        static::assertSame($response, $handledResponse);
    }

    #[DataProvider('provideRequestStacks')]
    public function testThrowsIfApiRespondsWithTokenExpiredException(RequestStack $requestStack): void
    {
        $response = new Response(401, [], '{"code":"CicadaPlatformException-1"}');

        $middleware = new StoreSessionExpiredMiddleware(
            $this->createMock(Connection::class),
            $requestStack
        );

        $this->expectException(StoreSessionExpiredException::class);
        $middleware($response);
    }

    public function testLogsOutUserAndThrowsIfApiRespondsWithTokenExpiredException(): void
    {
        $response = new Response(401, [], '{"code":"CicadaPlatformException-1"}');

        $adminUser = new UserEntity();
        $adminUser->setId('592d9499bea4417e929622ed0e92ba8b');

        $context = new Context(new AdminApiSource($adminUser->getId()));

        $request = new Request(
            [],
            [],
            [
                'sw-context' => $context,
            ]
        );

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('executeStatement')
            ->with(static::anything(), ['userId' => Uuid::fromHexToBytes($adminUser->getId())]);

        $middleware = new StoreSessionExpiredMiddleware(
            $connection,
            $requestStack
        );

        $this->expectException(StoreSessionExpiredException::class);
        $middleware($response);
    }

    public static function provideRequestStacks(): \Generator
    {
        yield 'request stack without request' => [new RequestStack()];

        $requestStackWithoutContext = new RequestStack();
        $requestStackWithoutContext->push(new Request());

        yield 'request stack without context' => [$requestStackWithoutContext];

        $requestStackWithWrongSource = new RequestStack();
        $requestStackWithWrongSource->push(new Request([], [], ['sw-context' => Context::createDefaultContext()]));

        yield 'request stack with wrong source' => [$requestStackWithWrongSource];

        $requestStackWithMissingUserId = new RequestStack();
        $requestStackWithMissingUserId->push(new Request([], [], ['sw-context' => new Context(new AdminApiSource(null))]));

        yield 'request stack with missing user id' => [$requestStackWithMissingUserId];
    }
}
