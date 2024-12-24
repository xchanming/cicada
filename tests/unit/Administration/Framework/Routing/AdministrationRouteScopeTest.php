<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Framework\Routing;

use Cicada\Administration\Framework\Routing\AdministrationRouteScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(AdministrationRouteScope::class)]
class AdministrationRouteScopeTest extends TestCase
{
    private AdministrationRouteScope $routeScope;

    protected function setUp(): void
    {
        $this->routeScope = new AdministrationRouteScope();
    }

    public function testIsAllowed(): void
    {
        static::assertTrue($this->routeScope->isAllowed(new Request()));
    }

    public function testGetId(): void
    {
        static::assertSame(AdministrationRouteScope::ID, $this->routeScope->getId());
    }

    public function testAllowedPaths(): void
    {
        static::assertSame(['admin', 'api'], $this->routeScope->getRoutePrefixes());
    }
}
