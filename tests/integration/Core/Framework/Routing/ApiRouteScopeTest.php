<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Routing;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\ContextSource;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Routing\ApiRouteScope;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ApiRouteScopeTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @return list<array{ContextSource, bool}>
     */
    public static function provideAllowedData(): array
    {
        return [
            [new AdminApiSource(null, null), true],
            [new AdminApiSource(null, null), false],
            [new SystemSource(), false],
        ];
    }

    /**
     * @return list<array{ContextSource, bool}>
     */
    public static function provideForbiddenData(): array
    {
        return [
            [new SalesChannelApiSource(Uuid::randomHex()), true],
            [new SystemSource(), true],
        ];
    }

    #[DataProvider('provideAllowedData')]
    public function testAllowedCombinations(ContextSource $source, bool $authRequired): void
    {
        $scope = static::getContainer()->get(ApiRouteScope::class);

        $request = Request::create('/api/foo');
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, Context::createDefaultContext($source));
        $request->attributes->set('auth_required', $authRequired);

        static::assertTrue($scope->isAllowedPath($request->getPathInfo()));
        static::assertTrue($scope->isAllowed($request));
    }

    #[DataProvider('provideForbiddenData')]
    public function testForbiddenCombinations(ContextSource $source, bool $authRequired): void
    {
        $scope = static::getContainer()->get(ApiRouteScope::class);

        $request = Request::create('/api/foo');
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, Context::createDefaultContext($source));
        $request->attributes->set('auth_required', $authRequired);

        static::assertFalse($scope->isAllowed($request));
    }
}
