<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Services;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Exception\StoreSessionExpiredException;
use Cicada\Core\Framework\Store\Services\StoreSessionExpiredMiddleware;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\User\UserCollection;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
class StoreSessionExpiredMiddlewareTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testReturnsResponseIfStatusCodeIsNotUnauthorized(): void
    {
        $response = new Response(200, [], '{"payload":"data"}');

        $middleware = new StoreSessionExpiredMiddleware(
            static::getContainer()->get(Connection::class),
            new RequestStack()
        );

        $handledResponse = $middleware($response);

        static::assertSame($response, $handledResponse);
    }

    public function testReturnsResponseWithRewoundBodyIfCodeIsNotMatched(): void
    {
        $response = new Response(401, [], '{"payload":"data"}');

        $middleware = new StoreSessionExpiredMiddleware(
            static::getContainer()->get(Connection::class),
            new RequestStack()
        );

        $handledResponse = $middleware($response);

        static::assertSame($response, $handledResponse);
    }

    #[DataProvider('provideRequestStacks')]
    public function testThrowsIfApiRespondsWithTokenExpiredException(RequestStack $requestStack): void
    {
        $response = new Response(401, [], '{"code":"CicadaPlatformException-1"}');

        $middleware = new StoreSessionExpiredMiddleware(
            static::getContainer()->get(Connection::class),
            $requestStack
        );

        $this->expectException(StoreSessionExpiredException::class);
        $middleware($response);
    }

    public function testLogsOutUserAndThrowsIfApiRespondsWithTokenExpiredException(): void
    {
        $response = new Response(401, [], '{"code":"CicadaPlatformException-1"}');

        /** @var EntityRepository<UserCollection> $userRepository */
        $userRepository = static::getContainer()->get('user.repository');
        $adminUser = $userRepository->search(new Criteria(), Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($adminUser);
        $userRepository->update([[
            'id' => $adminUser->getId(),
            'store_token' => 's3cr3t',
        ]], Context::createDefaultContext());

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

        $middleware = new StoreSessionExpiredMiddleware(
            static::getContainer()->get(Connection::class),
            $requestStack
        );

        $this->expectException(StoreSessionExpiredException::class);
        $middleware($response);

        $adminUser = $userRepository->search(new Criteria([$adminUser->getId()]), Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($adminUser);
        static::assertNull($adminUser->getStoreToken());
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
